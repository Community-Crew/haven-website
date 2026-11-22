<?php

namespace App\Services;

use App\Models\DefaultRoomRules;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomRuleAcceptance;
use Illuminate\Validation\ValidationException;

/**
 * The client echoes back the hash of the rules it showed, so an acceptance
 * can only ever count for the exact text the resident saw. If an admin edits
 * the rules in between, the hash no longer matches and the app asks again.
 */
class RoomRulesService
{
    public const ERROR_REQUIRED = 'room_rules_required';

    public const ERROR_OUTDATED = 'room_rules_outdated';

    /**
     * The reason code the given hash fails with, or null if it's fine.
     */
    public function failure(Room $room, ?string $acceptedHash): ?string
    {
        $currentHash = $room->rulesHash();

        if ($currentHash === null) {
            return null;
        }

        if (blank($acceptedHash)) {
            return self::ERROR_REQUIRED;
        }

        return hash_equals($currentHash, $acceptedHash) ? null : self::ERROR_OUTDATED;
    }

    public function ensureAccepted(Room $room, ?string $acceptedHash): void
    {
        if ($failure = $this->failure($room, $acceptedHash)) {
            throw ValidationException::withMessages(['rules_accepted_hash' => $failure]);
        }
    }

    /**
     * No-op for a room without rules: there's nothing to have accepted.
     */
    public function record(Reservation $reservation, Room $room, string $moment, int $userId): void
    {
        $hash = $room->rulesHash();

        if ($hash === null) {
            return;
        }

        RoomRuleAcceptance::create([
            'reservation_id' => $reservation->id,
            'user_id' => $userId,
            'room_id' => $room->id,
            'moment' => $moment,
            'rules_hash' => $hash,
            'default_rules' => DefaultRoomRules::current()->ruleList(),
            'rules' => $room->ruleList(),
            'accepted_at' => now(),
        ]);
    }
}
