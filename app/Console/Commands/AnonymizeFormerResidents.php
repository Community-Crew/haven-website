<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ResidentOffboardingService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Anonymizes everyone whose move-out retention period has passed. One
 * resident failing (e.g. Keycloak unreachable) doesn't stop the rest - it's
 * reported and simply picked up again on the next daily run.
 */
class AnonymizeFormerResidents extends Command
{
    protected $signature = 'app:anonymize-former-residents';

    protected $description = 'Anonymize residents who moved out more than '.ResidentOffboardingService::RETENTION_DAYS.' days ago';

    public function handle(ResidentOffboardingService $offboarding): int
    {
        $users = User::query()
            ->whereNull('anonymized_at')
            ->where('moved_out_at', '<=', now()->subDays(ResidentOffboardingService::RETENTION_DAYS))
            ->get();

        $failed = 0;

        foreach ($users as $user) {
            try {
                $offboarding->anonymize($user);
            } catch (Throwable $e) {
                report($e);
                $failed++;
                $this->error("Could not anonymize user #{$user->id}: {$e->getMessage()}");
            }
        }

        $this->info(sprintf('Anonymized %d former resident(s).', $users->count() - $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
