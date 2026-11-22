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
            /**
             * The user's own phone number, in international format.
             *
             * @example +31612345678
             */
            'phone' => $this->phone,
            /**
             * The user's own birth date.
             *
             * @example 1999-04-12
             */
            'birth_date' => $this->birth_date,
            /**
             * The open membership, or null for non-members.
             */
            'membership' => $this->currentMembership ? [
                'status' => $this->currentMembership->status->value,
                'identity_verified' => $this->currentMembership->identity_verified_at !== null,
                'documents_accepted' => $this->currentMembership->documents_accepted_at !== null,
                'is_beneficiary' => (bool) $this->currentMembership->memberType?->is_beneficiary,
                'is_honorary' => (bool) $this->currentMembership->memberType?->is_honorary,
            ] : null,
            /**
             * Moved out: the profile is all they can still use.
             */
            'is_former_resident' => $this->resource->isFormerResident(),
            /**
             * Only kept for former residents, whose unit is gone.
             */
            'postal_address' => $this->postal_address,
            /**
             * When a former resident's data is removed, or null if it stays
             * because they're a beneficiary or honorary member.
             */
            'anonymizes_at' => $this->moved_out_at && ! $this->resource->hasRetainedMembership()
                ? $this->resource->anonymizesAt()->toIso8601String()
                : null,
            'is_activated' => ! is_null($this->activated_at),
            'activated_at' => $this->activated_at ? $this->activated_at->toIso8601String() : null,
            'privacy_policy_accepted' => $this->resource->hasAcceptedCurrentPrivacyPolicy(),
            'privacy_policy_accepted_at' => $this->privacy_policy_accepted_at ? $this->privacy_policy_accepted_at->toIso8601String() : null,
            /**
             * The current user's app settings - same shape as GET /user/settings.
             */
            'settings' => new UserSettingsResource($this->resource),
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
