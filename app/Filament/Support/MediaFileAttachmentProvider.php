<?php

namespace App\Filament\Support;

use Filament\Forms\Components\RichEditor\FileAttachmentProviders\Contracts\FileAttachmentProvider;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use LogicException;

/**
 * Resolves rich-editor image nodes to the stable `/api/v1/media/{uuid}` URL
 * used throughout the app (see MediaRichEditor), for read-only rendering of
 * already-saved content via RichContentRenderer.
 *
 * Uploads are handled separately by MediaRichEditor's own closures, so the
 * write-path methods here are intentionally unsupported.
 */
class MediaFileAttachmentProvider implements FileAttachmentProvider
{
    public function attribute(RichContentAttribute $attribute): static
    {
        return $this;
    }

    public function getFileAttachmentUrl(mixed $file): ?string
    {
        return route('api.v1.media.show', ['media' => $file]);
    }

    public function saveUploadedFileAttachment(TemporaryUploadedFile $file): mixed
    {
        throw new LogicException('MediaFileAttachmentProvider is read-only; uploads are handled by MediaRichEditor.');
    }

    public function getDefaultFileAttachmentVisibility(): ?string
    {
        return 'private';
    }

    public function isExistingRecordRequiredToSaveNewFileAttachments(): bool
    {
        return false;
    }

    public function cleanUpFileAttachments(array $exceptIds): void
    {
        // No-op: read-only provider, nothing to clean up.
    }
}
