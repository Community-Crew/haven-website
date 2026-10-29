<?php

namespace App\Filament\Support;

use App\Models\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Same idea as MediaCoverPicker, but for a collection that can hold more
 * than one image (e.g. a room's several check-in example photos) instead of
 * a single cover. Unlike MediaCoverPicker this doesn't offer a "choose from
 * existing library" toggle - reusing library files across a multi-image
 * gallery hasn't come up as a need.
 *
 * The field's state is a plain array of Media paths (Filament's FileUpload
 * already treats an array of disk paths as "already uploaded" for preview
 * and reordering, the same way a single path does for MediaCoverPicker).
 * Call hydrate() from mutateFormDataBeforeFill() and sync() from an
 * afterCreate/afterSave hook, exactly like MediaCoverPicker.
 */
class MediaGalleryPicker
{
    /**
     * @return array<int, Component>
     */
    public static function formComponents(
        string $statePath,
        string $collection,
        string $label = 'Photos',
        bool $required = false,
    ): array {
        return [
            FileUpload::make($statePath)
                ->label($label)
                ->disk('hetzner')
                ->directory("media/{$collection}")
                ->visibility('private')
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->saveUploadedFileUsing(
                    fn (TemporaryUploadedFile $file) => Media::createFromUploadedFile($file, $collection)->path
                )
                ->required($required),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function hydrate(?Model $record, string $statePath, string $collection): array
    {
        if (! $record) {
            return [$statePath => []];
        }

        return [
            $statePath => self::mediaQuery($record, $collection)
                ->orderBy('created_at')
                ->pluck('path')
                ->all(),
        ];
    }

    /**
     * Call after the record is saved with the raw submitted form data.
     * Links every path still present to this record/collection and unlinks
     * whichever previously-linked photo was removed from the field.
     */
    public static function sync(Model $record, string $statePath, string $collection, array $data): void
    {
        $paths = $data[$statePath] ?? [];

        self::mediaQuery($record, $collection)
            ->whereNotIn('path', $paths)
            ->update(['mediable_type' => null, 'mediable_id' => null]);

        if ($paths === []) {
            return;
        }

        // $paths is client-submitted Livewire state, so it can name any file
        // on disk. Only link what this field could legitimately hold: files
        // in this collection that are either fresh (still unlinked) uploads
        // or already this record's - never another record's media.
        Media::query()
            ->where('collection', $collection)
            ->whereIn('path', $paths)
            ->where(fn ($query) => $query
                ->whereNull('mediable_id')
                ->orWhere(fn ($query) => $query
                    ->where('mediable_type', $record->getMorphClass())
                    ->where('mediable_id', $record->getKey())))
            ->update([
                'mediable_type' => $record->getMorphClass(),
                'mediable_id' => $record->getKey(),
            ]);
    }

    protected static function mediaQuery(Model $record, string $collection)
    {
        return Media::query()
            ->where('mediable_type', $record->getMorphClass())
            ->where('mediable_id', $record->getKey())
            ->where('collection', $collection);
    }
}
