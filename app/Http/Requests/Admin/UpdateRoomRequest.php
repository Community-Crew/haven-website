<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
'name'        => 'required|string',
'description' => 'required|string',
'location'    => 'required|string',
'status'      => [
'required',
new Enum(RoomStatus::class),
],
'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
];
    }
}
