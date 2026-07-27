<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
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
             * The unique internal identifier.
             *
             * @example 2
             */
            'id' => $this->id,
            /**
             * The name of the users.
             *
             * @example Jan Klaas
             */
            'name' => $this->name,
            /**
             * The email of the user.
             *
             * @example jan.klaas@voorbeeld.nl
             */
            'email' => $this->email,
            'is_activated' => ! is_null($this->activated_at),
            'activated_at' => $this->activated_at ? $this->activated_at->toIso8601String() : null,
            /**
             * The unit of the current user.
             */
            'unit' => $this->when($this->unit_id, function () {
                return [
                    /**
                     * The unique internal identifier of the unit of the current user.
                     *
                     * @example 2
                     */
                    'id' => $this->unit->id,
                    /**
                     * The building of the current user.
                     *
                     * @example Pollux
                     */
                    'building' => $this->unit->building,
                    /**
                     * The floor (or house number for terra) of the current user.
                     *
                     * @example 15
                     */
                    'floor' => $this->unit->floor,
                    /**
                     * The unit the current user is residing in.
                     *
                     * @example 01
                     */
                    'unit' => $this->unit->unit,
                    /**
                     * The optional subunit of the current user.
                     *
                     * @example E
                     */
                    'subunit' => $this->unit->subunit,
                ];
            }),
            /**
             * The roles the current user is part of.
             *
             * @example ["Resident"]
             */
            'roles' => $this->roles->pluck('name'),
        ];
    }
}
