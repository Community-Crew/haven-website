<?php

namespace App\Http\Resources\Api;

use App\Filament\Support\MediaFileAttachmentProvider;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivacyPolicyResource extends JsonResource
{
    /**
     * Transform the privacy policy into an optimized array.
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * The legally leading Dutch text - always present.
             */
            'content' => RichContentRenderer::make($this->content)
                ->fileAttachmentProvider(new MediaFileAttachmentProvider)
                ->toHtml(),
            /**
             * Courtesy English translation. Null until an admin fills it in;
             * the Dutch `content` above remains authoritative regardless.
             */
            'content_en' => $this->content_en
                ? RichContentRenderer::make($this->content_en)
                    ->fileAttachmentProvider(new MediaFileAttachmentProvider)
                    ->toHtml()
                : null,
            'authoritative_locale' => 'nl',
            'updated_at' => $this->updated_at,
        ];
    }
}
