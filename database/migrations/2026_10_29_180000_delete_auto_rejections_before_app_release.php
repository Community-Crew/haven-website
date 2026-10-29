<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Between v1.5.1 and this hotfix, app:auto-reject-missed-checkouts ran while
 * the app had no way to submit a check-out yet, so any auto-rejection from
 * that window is wrong. Auto-rejections (rejected check-outs with no
 * reviewer) are only created by that job, and from here on it stays off
 * until RESERVATION_CHECKS_LIVE_SINCE is set - so every one existing now is
 * from that window.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reservation_checks')
            ->where('type', 'check_out')
            ->where('verification_status', 'rejected')
            ->whereNull('reviewed_by')
            ->delete();
    }

    public function down(): void
    {
        // Deleted rows were bogus; nothing to restore.
    }
};
