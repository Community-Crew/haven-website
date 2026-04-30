<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreReservationPolicyRequest extends FormRequest
{
    public function authorize()
    {
        //        return Gate::allows('admin.reservation-policy.create');
        return true;
    }

    public function rules()
    {
        return [
            'max_days_in_advance' => 'required|integer',
            'role_name' => 'required|string',
            'room_ids' => 'required|array|min:1|',
            'room_ids.*' => 'integer|exists:rooms,id',
        ];
    }
}
