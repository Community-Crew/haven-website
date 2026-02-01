<?php

namespace App\Http\Requests\Utility;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return ['image' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,svg',
                'max:10240',
            ],];
    }
}
