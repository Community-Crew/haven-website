<?php

namespace App\Console\Commands;

use App\Traits\HasMediaAttachments;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;

class BackfillMediaAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-media-attachments {model? : Fully-qualified model class to backfill; all HasMediaAttachments models if omitted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-link Media rows to whichever record actually embeds them, for existing rows saved before HasMediaAttachments was wired up (or after manually editing the database)';

    public function handle(): void
    {
        $models = $this->argument('model')
            ? [$this->argument('model')]
            : $this->discoverModels();

        if (empty($models)) {
            $this->warn('No models using HasMediaAttachments were found.');

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

            if (! class_exists($class)) {
                continue;
            }

            if (in_array(HasMediaAttachments::class, class_uses_recursive($class))) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function backfill(string $modelClass): void
    {
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
            $query->withTrashed();
        }

        $count = 0;

        $query->each(function (Model $model) use (&$count) {
            $model->syncMediaAttachments();
            $count++;
        });

        $this->info("{$modelClass}: synced {$count} record(s).");
    }
}
