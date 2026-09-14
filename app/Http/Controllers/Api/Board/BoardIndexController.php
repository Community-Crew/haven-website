<?php

namespace App\Http\Controllers\Api\Board;

use App\Http\Resources\Api\BoardMemberResource;
use App\Models\BoardPositionAssignment;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * List the public board
 *
 * Fetch everyone currently holding a board position that's been marked
 * public - global board members (Voorzitter, Secretaris, ...) and
 * commission board members alike, including anyone holding several at
 * once. Public - no authentication required, feeds the website's public
 * board page.
 */
class BoardIndexController
{
    #[Group('Board')]
    public function __invoke(): AnonymousResourceCollection
    {
        $members = BoardPositionAssignment::query()
            ->whereNull('ended_at')
            ->where('is_public', true)
            ->with(['user', 'boardPosition.organisation'])
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return BoardMemberResource::collection($members);
    }
}
