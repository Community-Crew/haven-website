<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\MembershipDocument;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

class MembershipDocumentsController extends Controller
{
    /**
     * View membership documents
     *
     * The current statutes and house rules to accept when applying. Either
     * is null until the board has published it.
     */
    #[Group('Users')]
    public function __invoke(): JsonResponse
    {
        $present = fn (?MembershipDocument $document) => $document
            ? ['id' => $document->id, 'content' => $document->content]
            : null;

        return response()->json([
            'data' => [
                'statutes' => $present(MembershipDocument::current(MembershipDocument::STATUTES)),
                'rules' => $present(MembershipDocument::current(MembershipDocument::RULES)),
            ],
        ]);
    }
}
