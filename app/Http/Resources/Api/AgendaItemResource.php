<?php

namespace App\Http\Resources\Api;

use App\Filament\Support\MediaFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
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
            'description' => RichContentRenderer::make($this->description)
                ->fileAttachmentProvider(new MediaFileAttachmentProvider)
                ->toHtml(),
            'short_description' => $this->short_description,
            'image_url' => $this->image_url,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'agenda_id' => $this->agenda_id,
        ];
    }
}
