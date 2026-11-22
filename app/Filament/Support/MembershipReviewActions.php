<?php

namespace App\Filament\Support;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\User;
use App\Support\PostalAddress;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The board's review of a pending membership, usable on a User or Membership
 * record. Records who checked the ID and when; a copy or number of the ID
 * is deliberately never stored. Nobody can review their own application.
 */
class MembershipReviewActions
{
    public static function approve(): Action
    {
        return Action::make('approveMembership')
            ->label('Review')
            ->icon('heroicon-m-identification')
            ->color('success')
            ->visible(fn (Model $record) => self::canReview($record) && self::isReady(self::membership($record)))
            ->modalHeading('Review membership application')
            ->modalDescription(fn (Model $record) => self::summary(self::membership($record)))
            ->modalSubmitActionLabel('Approve')
            ->schema([
                Select::make('member_type_id')
                    ->label('Member type')
                    ->options(fn (Model $record) => self::typesFor(self::membership($record))->pluck('name', 'id'))
                    ->default(fn (Model $record) => self::membership($record)?->member_type_id)
                    ->required(),
                Checkbox::make('id_checked')
                    ->label('I checked their ID: name and birth date match. I did not keep a copy.')
                    ->accepted(),
            ])
            ->action(function (Model $record, array $data) {
                $membership = self::membership($record);
                $type = $membership ? self::typesFor($membership)->firstWhere('id', (int) $data['member_type_id']) : null;

                if (! $type || ! self::isReady($membership) || ! self::canReview($record)) {
                    Notification::make()->title('This application cannot be approved')->danger()->send();

                    return;
                }

                DB::transaction(function () use ($membership, $type) {
                    $membership->markIdentityVerified(Auth::user());
                    $membership->update([
                        'member_type_id' => $type->id,
                        'has_voting_rights' => $type->has_voting_rights,
                        'status' => MembershipStatus::ACTIVE,
                        'joined_at' => $membership->joined_at ?? now()->toDateString(),
                    ]);
                });

                Notification::make()->title('Application approved')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('rejectMembership')
            ->label('Reject')
            ->icon('heroicon-m-x-mark')
            ->color('danger')
            ->visible(fn (Model $record) => self::canReview($record))
            ->requiresConfirmation()
            ->modalHeading('Reject this application?')
            ->modalDescription('Ends the pending membership and emails the applicant. They can apply again later.')
            ->action(function (Model $record) {
                if (! self::canReview($record)) {
                    return;
                }

                self::membership($record)->update([
                    'status' => MembershipStatus::ENDED,
                    'ended_at' => now()->toDateString(),
                ]);

                Notification::make()->title('Application rejected')->success()->send();
            });
    }

    /**
     * Honorary members are appointed, never applied for, so this skips the
     * application. An open membership is converted rather than duplicated.
     */
    public static function appointHonorary(): Action
    {
        $canAppoint = fn (Model $record) => $record->anonymized_at === null
            && $record->id !== Auth::id()
            && ! $record->currentMembership?->memberType?->is_honorary
            && (bool) Auth::user()?->can('Update:Membership');

        return Action::make('appointHonorary')
            ->label('Appoint as erelid')
            ->icon('heroicon-m-star')
            ->color('warning')
            ->visible($canAppoint)
            ->modalHeading('Appoint as honorary member')
            ->modalDescription(fn (Model $record) => "Makes {$record->name} an erelid. This stays after moving out, and their data is kept until it ends.")
            ->modalSubmitActionLabel('Appoint')
            ->schema([
                Select::make('member_type_id')
                    ->label('Member type')
                    ->options(fn () => MemberType::where('is_active', true)->where('is_honorary', true)->orderBy('sort_order')->pluck('name', 'id'))
                    ->default(fn () => MemberType::where('is_active', true)->where('is_honorary', true)->orderBy('sort_order')->value('id'))
                    ->required(),
                Checkbox::make('id_checked')
                    ->label('I checked their ID: name and birth date match. I did not keep a copy.')
                    ->accepted(),
            ])
            ->action(function (Model $record, array $data) use ($canAppoint) {
                $type = MemberType::where('is_active', true)->where('is_honorary', true)->find($data['member_type_id']);

                if (! $type || ! $canAppoint($record)) {
                    Notification::make()->title('This person cannot be appointed')->danger()->send();

                    return;
                }

                DB::transaction(function () use ($record, $type) {
                    $membership = $record->currentMembership()->first()
                        ?? Membership::create(['user_id' => $record->id, 'status' => MembershipStatus::PENDING]);

                    $membership->markIdentityVerified(Auth::user());
                    $membership->update([
                        'member_type_id' => $type->id,
                        'has_voting_rights' => $type->has_voting_rights,
                        'status' => MembershipStatus::ACTIVE,
                        'joined_at' => $membership->joined_at ?? now()->toDateString(),
                    ]);
                });

                Notification::make()->title('Honorary member appointed')->success()->send();
            });
    }

    /**
     * Everything the applicant has to do is done; it's up to the board now.
     */
    public static function isReady(?Membership $membership): bool
    {
        return $membership?->status === MembershipStatus::PENDING
            && $membership->documents_accepted_at !== null
            && $membership->user->hasCompleteMemberDetails();
    }

    private static function membership(Model $record): ?Membership
    {
        return $record instanceof User ? $record->currentMembership : $record;
    }

    private static function canReview(Model $record): bool
    {
        $membership = self::membership($record);

        return $membership?->status === MembershipStatus::PENDING
            && $membership->user_id !== Auth::id()
            && (bool) Auth::user()?->can('update', $membership);
    }

    /**
     * Residents are members; former residents can only be beneficiaries.
     * Honorary types aren't here: those are appointed, see appointHonorary().
     */
    private static function typesFor(?Membership $membership)
    {
        return MemberType::query()
            ->where('is_active', true)
            ->where('is_honorary', false)
            ->where('is_beneficiary', (bool) $membership?->user->moved_out_at)
            ->orderBy('sort_order')
            ->get();
    }

    private static function summary(?Membership $membership): string
    {
        $user = $membership?->user;
        $address = PostalAddress::format($user?->postal_address);

        return sprintf(
            '%sCheck the ID of %s, born %s. Phone: %s.%s Accepted statutes and house rules on %s.',
            $user?->moved_out_at ? 'Former resident applying as beneficiary. ' : '',
            $user?->name ?? '-',
            $user?->birth_date ? Carbon::parse($user->birth_date)->format('d-m-Y') : '-',
            $user?->phone ?? '-',
            $address ? " Address: {$address}." : '',
            $membership?->documents_accepted_at?->format('d-m-Y H:i') ?? '-',
        );
    }
}
