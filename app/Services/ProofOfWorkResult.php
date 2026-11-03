<?php

namespace App\Services;

/**
 * Outcome of ProofOfWork::verify(): either accepted, with how many seconds
 * the challenge had been out, or refused with a reason code.
 */
final class ProofOfWorkResult
{
    private function __construct(
        public readonly ?string $failure,
        public readonly ?int $age,
    ) {}

    public static function from(string|int $outcome): self
    {
        return is_int($outcome) ? new self(null, $outcome) : new self($outcome, null);
    }

    public function passed(): bool
    {
        return $this->failure === null;
    }
}
