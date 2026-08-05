<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Traits\HasCoverMedia;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackfillCoverImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-cover-images {model? : Fully-qualified model class to backfill; all HasCoverMedia models with an image_path column if omitted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Media rows (and link them via coverMedia) for existing image_path values, from before cover images moved onto the Media system - does not reprocess/recompress the files, just points a Media row at what is already on disk';

    public function handle(): void
    {
        $models = $this->argument('model')
            ? [$this->argument('model')]
            : $this->discoverModels();

        if (empty($models)) {
            $this->warn('No HasCoverMedia models with an image_path column were found.');

            return;
        }

        foreach ($models as $modelClass) {
            $this->backfill($modelClass);
        }
    }

    /**
     * @return array<int, class-string<Model>>
     */
    protected function discoverModels(): array
    {
        $models = [];

        foreach (File::allFiles(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
                continue;
            }

            if (! in_array(HasCoverMedia::class, class_uses_recursive($class))) {
                continue;
            }

            if (! Schema::hasColumn((new $class)->getTable(), 'image_path')) {
                continue;
            }

            $models[] = $class;
        }

        return $models;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function backfill(string $modelClass): void
    {
        $query = $modelClass::query()->whereNotNull('image_path');

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }

        $created = 0;
        $skipped = 0;

        $query->each(function (Model $model) use (&$created, &$skipped) {
            $collection = $model->getCoverMediaCollection();

            if ($model->coverMedia($collection) !== null) {
                $skipped++;

                return;
            }

            $path = $model->getAttribute('image_path');

            $media = Media::create([
                'disk' => 'hetzner',
                'path' => $path,
                'collection' => $collection,
                'name' => basename($path),
                'mediable_type' => $model->getMorphClass(),
                'mediable_id' => $model->getKey(),
            ]);

            $created++;

            $this->line("  linked {$path} -> {$model->getMorphClass()}#{$model->getKey()} (Media #{$media->id})");
        });

        $this->info("{$modelClass}: created {$created}, skipped {$skipped} (already had a cover).");
    }
}
