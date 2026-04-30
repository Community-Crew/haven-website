<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreReservationPolicyEntryRequest extends FormRequest
{
    public function authorize()
    {
//        return Gate::allows('admin.reservation-policy.create');
        return true;
    }

    public function rules()
    {
        return [
            'day_of_week' => ['required', 'integer', 'regex:/^([0-6]|8)$/'],
            'start_time' => [
                'required',
                'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/'
            ],

            'end_time' => [
                'required',
                'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]|24:00$/',
                function ($attribute, $value, $fail) {
                    $start = $this->input('start_time');
                    if ($start && $value <= $start && $value !== '24:00') {
                        $fail('The end time must be after the start time.');
                    }
                },
            ],

            'reservation_policy_id' => ['required', 'exists:reservation_policies,id'],
        ];
    }
}
