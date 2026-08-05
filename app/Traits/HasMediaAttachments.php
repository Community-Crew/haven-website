<?php

namespace App\Traits;

use App\Models\Media;

/**
 * Keeps Media.mediable_type/mediable_id in sync with whichever record's
 * rich-text column(s) actually embed each file.
 *
 * MediaRichEditor bakes a stable `/api/v1/media/{uuid}` URL into saved
 * content at upload time, but the parent record doesn't exist yet on a
 * create page - so the association can only be made after the model is
 * actually saved. This trait does that: on every save it re-scans the
 * configured rich-text columns, claims any newly-referenced Media rows, and
 * releases ones that were removed from the content (they become unowned
 * again rather than being deleted, since removing a paragraph shouldn't
 * silently delete a file - see `deleteMediaAttachments()` for the one case
 * where outright deletion is correct: a true force-delete of the record).
 */
trait HasMediaAttachments
{
    protected static function bootHasMediaAttachments(): void
    {
        static::saved(function ($model) {
            $model->syncMediaAttachments();
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->deleteMediaAttachments();
        });
    }

    /**
     * Column names on this model containing rich-text content that may
     * embed Media attachments. Defaults to the model's $mediaAttachmentColumns
     * property; override this method for anything more dynamic.
     */
    public function getMediaAttachmentColumns(): array
    {
        return $this->mediaAttachmentColumns ?? [];
    }

    /**
     * Link every Media file currently referenced in this model's rich-text
     * columns to this record, and release any that no longer are.
     */
    public function syncMediaAttachments(): void
    {
        $columns = $this->getMediaAttachmentColumns();

        if (empty($columns)) {
            return;
        }

        $uuids = collect($columns)
            ->flatMap(fn (string $column) => static::extractMediaUuids((string) $this->getAttribute($column)))
            ->unique()
            ->values();

        if ($uuids->isNotEmpty()) {
            Media::query()
                ->whereIn('uuid', $uuids)
                ->update([
                    'mediable_type' => $this->getMorphClass(),
                    'mediable_id' => $this->getKey(),
                ]);
        }

        Media::query()
            ->where('mediable_type', $this->getMorphClass())
            ->where('mediable_id', $this->getKey())
            ->when($uuids->isNotEmpty(), fn ($query) => $query->whereNotIn('uuid', $uuids))
            ->update(['mediable_type' => null, 'mediable_id' => null]);
    }

    /**
     * Permanently delete every Media attachment still linked to this record,
     * including the underlying files. Only ever called on a true force-delete.
     */
    public function deleteMediaAttachments(): void
    {
        Media::query()
            ->where('mediable_type', $this->getMorphClass())
            ->where('mediable_id', $this->getKey())
            ->get()
            ->each->delete();
    }

    /**
     * @return array<int, string>
     */
    public static function extractMediaUuids(string $content): array
    {
        preg_match_all(
            '#/api/v1/media/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})#',
            $content,
            $matches,
        );

        return $matches[1] ?? [];
    }
}
