<?php

namespace App\Http\Controllers\Api\Health;

use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
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
     * "v1.1.0"), if HEAD was exactly a tagged release at deploy time. Falls
     * back to the short commit SHA between releases, rather than showing a
     * stale tag from an earlier release.
     *
     * Read from storage/app/version.txt, written by deploy.yml's deploy
     * step - production runs PHP against a copy of the checkout that
     * doesn't have a working .git (no repo access from the php-fpm user),
     * so shelling out to git here would silently return null. Falls back
     * to shelling out to git directly for local/dev, where that file won't
     * exist. Cached briefly to avoid a filesystem/process hit on every
     * request.
     */
    private function version(): ?string
    {
        return Cache::remember('app.version', now()->addMinutes(5), function () {
            $versionFile = storage_path('app/version.txt');

            if (File::exists($versionFile)) {
                $version = trim(File::get($versionFile));

                return $version !== '' ? $version : null;
            }

            $tag = Process::path(base_path())->run('git describe --tags --exact-match HEAD');

            if ($tag->successful()) {
                return trim($tag->output());
            }

            $sha = Process::path(base_path())->run('git rev-parse --short HEAD');

            return $sha->successful() ? trim($sha->output()) : null;
        });
    }
}
