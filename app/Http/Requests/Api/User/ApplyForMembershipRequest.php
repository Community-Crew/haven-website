<?php

namespace App\Http\Requests\Api\User;

use App\Http\Requests\Api\BaseApiRequest;
use App\Support\PostalAddress;

class ApplyForMembershipRequest extends BaseApiRequest
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
            'phone' => ['required', 'string', 'phone:INTERNATIONAL,NL'],
            /**
             * Birth date.
             *
             * @example 1999-04-12
             */
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            /**
             * The version of the statutes shown to the resident.
             */
            'statutes_document_id' => ['required', 'integer'],
            /**
             * The version of the house rules shown to the resident.
             */
            'rules_document_id' => ['required', 'integer'],
            /**
             * Must be true: the resident accepts both documents.
             */
            'documents_accepted' => ['accepted'],
            // Only former residents: a resident's address is their unit.
            ...PostalAddress::rules($this->user()->isFormerResident() ? 'required' : 'prohibited'),
        ];
    }
}
