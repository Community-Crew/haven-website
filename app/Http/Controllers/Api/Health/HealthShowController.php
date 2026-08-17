<?php

namespace App\Http\Controllers\Api\Health;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class HealthShowController
{
    /**
     * Server Health
     *
     * Liveness check for uptime monitoring - confirms the app can respond
     * and reports the currently deployed version. Database connectivity is
     * monitored separately, so it's deliberately not checked here.
     */
    #[Group('Health')]
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'version' => $this->version(),
        ]);
    }

    /**
     * The GitHub release tag for the currently deployed commit (e.g.
     * "v1.1.0"), if HEAD is exactly a tagged release. Falls back to the
     * short commit SHA between releases, rather than showing a stale tag
     * from an earlier release. Cached briefly so this doesn't shell out to
     * git on every hit - deploy.yml does a `git reset --hard`, so this
     * reflects what's actually live within a few minutes of a deploy.
     */
    private function version(): ?string
    {
        return Cache::remember('app.version', now()->addMinutes(5), function () {
            $tag = Process::path(base_path())->run('git describe --tags --exact-match HEAD');

            if ($tag->successful()) {
                return trim($tag->output());
            }

            $sha = Process::path(base_path())->run('git rev-parse --short HEAD');

            return $sha->successful() ? trim($sha->output()) : null;
        });
    }
}
