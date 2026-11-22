<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\PostalAddress;
use Illuminate\Validation\Validator;

class UpdateContactDetailsRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Phone number; Dutch unless it has a country code.
             *
             * @example 0612345678
             */
            'phone' => ['sometimes', 'required', 'string', 'phone:INTERNATIONAL,NL'],
            /**
             * Birth date, members only.
             *
             * @example 1999-04-12
             */
            'birth_date' => ['sometimes', 'required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            // Only former residents: a resident's address is their unit.
            ...PostalAddress::rules($this->user()->isFormerResident() ? 'sometimes' : 'prohibited'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->has('birth_date') || $validator->errors()->has('birth_date')) {
                    return;
                }

                $membership = $this->user()->currentMembership()->first();

                if (! $membership) {
                    $validator->errors()->add('birth_date', 'birth_date_members_only');
                } elseif ($membership->identity_verified_at
                    && $this->input('birth_date') !== $this->user()->birth_date) {
                    // The board checked this date against an ID.
                    $validator->errors()->add('birth_date', 'birth_date_locked');
                }
            },
        ];
    }
}
