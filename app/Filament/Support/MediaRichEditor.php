<?php

namespace App\Filament\Support;

use App\Models\Media;
use Filament\Forms\Components\RichEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A RichEditor preconfigured to store embedded images as Media records on
 * the private `hetzner` disk, addressed by a stable `/api/v1/media/{uuid}`
 * URL rather than a baked-in signed URL. Filament persists whatever
 * `saveUploadedFileAttachmentUsing` returns straight into the saved content,
 * so the URL that ends up in the database never expires - resolving the
 * UUID to a fresh signed URL happens at request time, in MediaShowController.
 */
class MediaRichEditor
{
    public static function make(string $name, string $collection): RichEditor
    {
        return RichEditor::make($name)
            ->fileAttachmentsDisk('hetzner')
            ->fileAttachmentsDirectory("media/{$collection}")
            ->fileAttachmentsVisibility('private')
            ->saveUploadedFileAttachmentUsing(
                fn (TemporaryUploadedFile $file) => Media::createFromUploadedFile($file, $collection)->uuid
            )
            ->getFileAttachmentUrlUsing(fn (string $file): string => route('api.v1.media.show', ['media' => $file]));
    }
}
