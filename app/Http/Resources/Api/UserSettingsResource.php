<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * Language for the app and for mails sent to this user.
             *
             * @example nl
             */
            'locale' => $this->locale,
            /**
             * Colour scheme: system, light or dark.
             *
             * @example system
             */
            'theme' => $this->theme,
        ];
    }
}
