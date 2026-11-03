<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Hashcash-style proof of work for making reservations, against booking by
 * script. The app fetches a challenge as the booking form opens and finds
 * a counter for which sha256("<challenge>:<counter>") starts with
 * `difficulty` zero bits - in the background, while the resident is still
 * picking a slot, so a person never waits for it.
 *
 * A challenge is a signed (not encrypted - nothing in it is secret) token
 * bound to one user, valid between min_age and max_age seconds after it
 * was issued, and accepted once. The minimum age is what stops a script
 * from fetching, solving and submitting in one go; fetching challenges is
 * rate-limited too, so they can't be stockpiled ahead of time.
 *
 * It doesn't make a script slower than a person at a fixed moment like the
 * evening window opening - see config/reservations.php's window_open_time
 * for what addresses that.
 */
class ProofOfWork
{
    public function enabled(): bool
    {
        return $this->difficulty() > 0;
    }

    public function difficulty(): int
    {
        return (int) config('reservations.proof_of_work.difficulty');
    }

    /**
     * @return array{challenge: string, difficulty: int, min_age: int, expires_in: int}
     */
    public function issue(User $user): array
    {
        $payload = $this->encode([
            'u' => $user->getKey(),
            'n' => Str::random(24),
            't' => now()->getTimestamp(),
            'd' => $this->difficulty(),
        ]);

        return [
            'challenge' => $payload.'.'.$this->sign($payload),
            'difficulty' => $this->difficulty(),
            'min_age' => (int) config('reservations.proof_of_work.min_age'),
            'expires_in' => (int) config('reservations.proof_of_work.max_age'),
        ];
    }

    /**
     * Accepted (and the challenge now used up), or why not - as a short
     * reason code for the API response. An accepted one also reports how
     * long the challenge had been out, for Reservation::bookingSignals().
     */
    public function verify(User $user, ?string $challenge, mixed $counter): ProofOfWorkResult
    {
        return ProofOfWorkResult::from($this->check($user, $challenge, $counter));
    }

    /**
     * @return string|int the failure reason, or the challenge's age in seconds
     */
    protected function check(User $user, ?string $challenge, mixed $counter): string|int
    {
        if (! is_string($challenge) || ! str_contains($challenge, '.') || ! is_scalar($counter) || ! ctype_digit((string) $counter)) {
            return 'missing';
        }

        [$payload, $signature] = explode('.', $challenge, 2);

        if (! hash_equals($this->sign($payload), $signature)) {
            return 'invalid';
        }

        $data = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);

        if (! is_array($data) || ($data['u'] ?? null) !== $user->getKey()) {
            return 'invalid';
        }

        $age = now()->getTimestamp() - (int) $data['t'];

        if ($age < (int) config('reservations.proof_of_work.min_age')) {
            return 'too_fast';
        }

        if ($age > (int) config('reservations.proof_of_work.max_age')) {
            return 'expired';
        }

        // The difficulty it was issued with (it's signed), so changing the
        // config doesn't invalidate challenges already being solved. At
        // least 1, so one issued while the check was off isn't free.
        if ($this->leadingZeroBits(hash('sha256', $challenge.':'.$counter, true)) < max((int) $data['d'], 1)) {
            return 'unsolved';
        }

        if (! Cache::add('pow:used:'.$data['n'], true, (int) config('reservations.proof_of_work.max_age') + 60)) {
            return 'reused';
        }

        return $age;
    }

    public function leadingZeroBits(string $binary): int
    {
        $bits = 0;

        foreach (str_split($binary) as $byte) {
            $value = ord($byte);

            if ($value === 0) {
                $bits += 8;

                continue;
            }

            return $bits + (7 - (int) floor(log($value, 2)));
        }

        return $bits;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function encode(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }

    protected function sign(string $payload): string
    {
        return hash_hmac('sha256', 'reservation-pow|'.$payload, config('app.key'));
    }
}
