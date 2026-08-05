<?php

namespace App\Filament\Support;

use App\Models\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A cover-image picker that lets the user either upload a new image (run
 * through Media::createFromUploadedFile - same resize/compress pipeline as
 * everywhere else) or search the existing Media library and reuse a file
 * already on disk, instead of always uploading a fresh copy.
 *
 * Both controls share one virtual form field, toggled by visibility. Its
 * value is always a Media *path* string (not a UUID) - that's the one
 * representation FileUpload understands natively for previewing an already-
 * chosen image via ->disk(), so hydrating the field with an existing cover's
 * path "just works" without a custom preview.
 *
 * The model using this must expose `coverMedia(string $collection): ?Media`
 * (see HasCoverMedia). Call hydrate() from the resource's
 * mutateFormDataBeforeFill() and sync() from an afterSave/afterCreate hook
 * (or a RelationManager action's ->after()).
 */
class MediaCoverPicker
{
    /**
     * @return array<int, Component>
     */
    public static function formComponents(
        string $statePath,
        string $collection,
        string $label = 'Cover Image',
        bool $required = false,
    ): array {
        $toggleKey = "{$statePath}_use_existing";

        return [
            Toggle::make($toggleKey)
                ->label('Choose from existing library instead of uploading')
                ->live()
                ->dehydrated(false)
                ->default(false)
                ->afterStateUpdated(fn (callable $set) => $set($statePath, null)),

            FileUpload::make($statePath)
                ->label($label)
                ->disk('hetzner')
                ->directory("media/{$collection}")
                ->visibility('private')
                ->image()
                ->imageAspectRatio('3:2')
                ->automaticallyCropImagesToAspectRatio()
                ->saveUploadedFileUsing(
                    fn (TemporaryUploadedFile $file) => Media::createFromUploadedFile($file, $collection)->path
                )
                ->required($required)
                ->visible(fn (Get $get): bool => ! $get($toggleKey))
                ->dehydrated(fn (Get $get): bool => ! $get($toggleKey)),

            Select::make($statePath)
                ->label('Choose Existing Image')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => self::searchResults($search))
                ->getOptionLabelUsing(fn ($value): ?string => self::labelForPath($value))
                ->required($required)
                ->visible(fn (Get $get): bool => (bool) $get($toggleKey))
                ->dehydrated(fn (Get $get): bool => (bool) $get($toggleKey)),
        ];
    }

    /**
     * Merge into mutateFormDataBeforeFill()'s return value so the field
     * starts populated with whatever the record's current cover already is.
     *
     * @return array<string, mixed>
     */
    public static function hydrate(?Model $record, string $statePath, string $collection): array
    {
        if (! $record || ! method_exists($record, 'coverMedia')) {
            return [$statePath => null];
        }

        return [$statePath => $record->coverMedia($collection)?->path];
    }

    /**
     * Call after the record is saved (afterCreate/afterSave, or an Action's
     * ->after()) with the raw submitted form data. Links whichever Media the
     * field points to as this record's cover for the collection, and
     * releases whatever was previously linked if it changed. Leaving the
     * field untouched (still whatever hydrate() set it to) is a no-op.
     */
    public static function sync(Model $record, string $statePath, string $collection, array $data): void
    {
        $path = $data[$statePath] ?? null;

        $current = $record->coverMedia($collection);

        if ($current && $current->path === $path) {
            return;
        }

        if ($current) {
            $current->update(['mediable_type' => null, 'mediable_id' => null]);
        }

        if ($path === null) {
            return;
        }

        Media::query()
            ->where('path', $path)
            ->update([
                'mediable_type' => $record->getMorphClass(),
                'mediable_id' => $record->getKey(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function searchResults(string $search): array
    {
        return Media::query()
            ->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('collection', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Media $media): array => [$media->path => self::label($media)])
            ->all();
    }

    protected static function labelForPath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $media = Media::query()->where('path', $path)->first();

        return $media ? self::label($media) : null;
    }

    protected static function label(Media $media): string
    {
        $name = $media->name ?: 'Untitled';
        $size = $media->size ? Number::fileSize($media->size) : '?';

        return "{$name} · {$media->collection} · {$size}";
    }
}
