<?php

namespace App\Rules;

use App\Models\RegistrationCode;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRegistrationCode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $code = RegistrationCode::where('code', $value)->first();

        if (! $code) {
            $fail('Registration code not found.');

            return;
        }

        if ($code->is_used) {
            $fail('Registration code has been used.');

            return;
        }
    }
}
