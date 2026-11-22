<?php

namespace App\Http\Controllers\Api\Room;

use App\Http\Controllers\Controller;
use App\Models\DefaultRoomRules;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomRulesController extends Controller
{
    /**
     * The default rules and this room's own, plus the hash to send back when
     * accepting them. The hash is null if there are no rules at all.
     */
    public function __invoke(Room $room): JsonResponse
    {
        return response()->json([
            'data' => [
                'default_rules' => DefaultRoomRules::current()->ruleList(),
                'rules' => $room->ruleList(),
                'rules_hash' => $room->rulesHash(),
            ],
        ]);
    }
}
