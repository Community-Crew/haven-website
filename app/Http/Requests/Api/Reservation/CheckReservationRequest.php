<?php

namespace App\Http\Requests\Api\Reservation;

use Illuminate\Foundation\Http\FormRequest;

class CheckReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'heic' deliberately left out of mimes: it passes this
            // validation fine, but the app's image pipeline (Media::
            // createFromUploadedFile, via Intervention's GD driver) can't
            // decode it, which turned "invalid file" into an unhandled
            // 500 instead of a clean 422 - see Media::createFromUploadedFile.
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:10240'],
        ];
    }
}
