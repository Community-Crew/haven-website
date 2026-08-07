<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

class CleanupUnusedMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-unused-media
        {--days=7 : Grace period in days since a Media row last changed before it is eligible for deletion}
        {--dry-run : List what would be deleted without actually deleting anything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Media rows (and their underlying files) that are not linked to any record - e.g. an upload whose parent record edit was abandoned, or an image removed from rich text/a cover slot and never reused';

    public function handle(): void
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        $query = Media::query()
            ->whereNull('mediable_type')
            ->whereNull('mediable_id')
            ->where('updated_at', '<', now()->subDays($days));

        $count = $query->count();

        if ($count === 0) {
            $this->info("No unused Media rows older than {$days} day(s).");

            return;
        }

        if ($dryRun) {
            $this->info("Would delete {$count} unused Media row(s) (dry run, nothing changed):");

            $query->get()->each(fn (Media $media) => $this->line("  {$media->path} (collection: {$media->collection}, last touched {$media->updated_at})"));

            return;
        }

        // Deleted one at a time (not a bulk query delete) so Media's own
        // `deleting` hook fires per row and actually removes each file from
        // its disk, not just the database record.
        $query->get()->each(fn (Media $media) => $media->delete());

        $this->info("Deleted {$count} unused Media row(s) older than {$days} day(s).");
    }
}
