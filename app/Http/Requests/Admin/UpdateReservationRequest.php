<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
'name'       => [
                'required',
                'string',
                'max:255',
            ],
'email'      => [
                'required',
                'email',
                'max:255',
            ],
'room'       => [
                'required',
                'exists:rooms,id',
            ],
'status'     => [
                'required',
            ],
'start_time' => [
                'required',
                'date',
                'after:now',
            ],
'end_time'   => [
                'required',
                'date',
                'after:start_time',
            ],
];
    }
}
