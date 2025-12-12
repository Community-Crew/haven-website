<?php

namespace App\Rules;

use App\Models\ReservationPolicy;
use App\Services\TimeSlotService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Translation\PotentiallyTranslatedString;

class ReservationCompliesWithRolePolicy implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $startString = request()->input('start_time');
        $endString   = request()->input('end_time');

        if (!$startString || !$endString) return;

        $reqStart = Carbon::parse($startString);
        $reqEnd   = Carbon::parse($endString);

        $service = new TimeSlotService();

        $validRanges = $service->getMergedTimeSlots($reqStart);

        if (empty($validRanges)) {
            $fail('No booking options available.');
            return;
        }

        $fits = false;
        $reqStartTime = $reqStart->format('H:i');
        $reqEndTime   = $reqEnd->format('H:i');

        foreach ($validRanges as $range) {
            if($reqStartTime >= $range['start'] && $reqStartTime <= $range['end']) {
                $fits = true;
                break;
            }
        }

        if (!$fits) {
            $availableStr = collect($validRanges)
                ->map(fn($r)=> "{$r['start']} to {$r['end']}")
                ->join(', ');

            $fail("Time unavailable. Your allowed slots are: $availableStr");
        }




    }
}
