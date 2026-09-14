<?php

namespace App\Http\Controllers\Api\Board;

use App\Enums\MembershipStatus;
use App\Http\Resources\Api\BoardMemberResource;
use App\Models\Membership;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * List the public board
 *
 * Fetch everyone currently holding a board position that's been marked
 * public - global board members (Voorzitter, Secretaris, ...) and
 * commission board members alike. Public - no authentication required,
 * feeds the website's public board page.
 */
class BoardIndexController
{
    #[Group('Board')]
    public function __invoke(): AnonymousResourceCollection
    {
        $members = Membership::query()
            ->whereNotNull('board_position_id')
            ->where('is_public', true)
            ->whereIn('status', array_map(fn (MembershipStatus $status) => $status->value, MembershipStatus::open()))
            ->with(['user', 'boardPosition.organisation'])
            ->orderBy('sort_order')
            ->orderBy('joined_at')
            ->get();

        return BoardMemberResource::collection($members);
    }
}
