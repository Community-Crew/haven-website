<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
             * @example 2
             */
            'id' => $this->id,

            /**
             * The display name of the room.
             * @example "Hangout Downstairs"
             */
            'name' => $this->name,

            /**
             * The slug of the room.
             * @example "hangout-downstairs"
             */
            'slug' => $this->slug,

            /**
             * The description of the room.
             * @example "Hangout time is vibing time."
             */
            'description' => $this->description,

            /**
             * The location of the room.
             * @example "Blauwe Loper 61A"
             */
            'location' => $this->location,

            /**
             * The status of the room.
             * @example "available"
             */
            'status' => $this->status->value,

            /**
             * The temporary image url of the S3 bucket.
             * @example "https://fsn1.your-objectstorage.com/havencommunity/placeholder.gif?[...]576"
             */
            'image_url' => $this->image_url
        ];
    }
}
