<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgendaItemResource extends JsonResource
{
    /**
     * Transform the nested agenda item details into an optimized array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'image_url' => $this->image_url,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'agenda_id' => $this->agenda_id,
        ];
    }
}
