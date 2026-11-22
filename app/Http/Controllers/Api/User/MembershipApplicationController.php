<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\ApplyForMembershipRequest;
use App\Http\Resources\Api\UserProfileResource;
use App\Models\Membership;
use App\Models\MembershipDocument;
use App\Models\MemberType;
use App\Models\User;
use App\Support\PostalAddress;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Propaganistas\LaravelPhone\PhoneNumber;
use Symfony\Component\HttpFoundation\Response;

class MembershipApplicationController extends Controller
{
    /**
     * Apply for membership
     *
     * The resident fills in their details and accepts the statutes and house
     * rules. The membership stays pending until the board approves it. A
     * pending membership the board created is completed the same way.
     */
    #[Group('Users')]
    public function __invoke(ApplyForMembershipRequest $request): JsonResponse
    {
        $userId = $request->user()->getKey();

        // Locked so a double tap can't create two open memberships.
        $refusal = DB::transaction(function () use ($request, $userId) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $open = $user->currentMembership()->first();

            if ($open && ($open->status !== MembershipStatus::PENDING || $open->documents_accepted_at)) {
                return 'MEMBERSHIP_ALREADY_OPEN';
            }

            $statutes = MembershipDocument::current(MembershipDocument::STATUTES);
            $rules = MembershipDocument::current(MembershipDocument::RULES);

            // Residents become members, with the type picked by the board.
            // Former residents can only become a beneficiary.
            $beneficiaryType = $user->isFormerResident()
                ? MemberType::where('is_beneficiary', true)->where('is_active', true)->orderBy('sort_order')->first()
                : null;

            if (! $statutes || ! $rules || ($user->isFormerResident() && ! $beneficiaryType)) {
                return 'APPLICATIONS_CLOSED';
            }

            if ($statutes->id !== $request->integer('statutes_document_id')
                || $rules->id !== $request->integer('rules_document_id')) {
                return 'DOCUMENTS_OUTDATED';
            }

            $user->phone = (new PhoneNumber($request->input('phone'), 'NL'))->formatE164();

            // A date the board already checked against an ID stays as it is.
            if (! $open?->identity_verified_at) {
                $user->birth_date = $request->input('birth_date');
            }

            if ($beneficiaryType) {
                $user->postal_address = PostalAddress::normalize($request->validated('postal_address'));
            }

            $user->save();

            $membership = $open ?? new Membership([
                'user_id' => $userId,
                'status' => MembershipStatus::PENDING,
            ]);

            if ($beneficiaryType) {
                $membership->member_type_id = $beneficiaryType->id;
                $membership->has_voting_rights = $beneficiaryType->has_voting_rights;
            }

            $membership->forceFill([
                'statutes_document_id' => $statutes->id,
                'rules_document_id' => $rules->id,
                'documents_accepted_at' => now(),
            ])->save();

            return null;
        });

        if ($refusal) {
            return $this->refuse($refusal, 'The application could not be processed.', Response::HTTP_CONFLICT);
        }

        return (new UserProfileResource(User::with(['unit', 'roles'])->findOrFail($userId)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function refuse(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['errors' => $code, 'message' => $message], $status);
    }
}
