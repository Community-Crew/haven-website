<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * (Already ran in production - the cutoff below is v1.5.1's deploy time,
 * kept literal so this stays fixed whatever the command's config says.)
 *
 * The first production run of AutoRejectMissedCheckouts (v1.5.1) had no
 * cutoff and auto-rejected the check-out of every approved reservation ever
 * made - long before residents could check out at all. Those rows would
 * otherwise count as "missed" in residents' review history and on the
 * dashboard. Auto-rejections are the only rejected check-outs with no
 * reviewer and never carry photos, so nothing else matches.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reservation_checks')
            ->where('type', 'check_out')
            ->where('verification_status', 'rejected')
            ->whereNull('reviewed_by')
            ->whereIn('reservation_id', DB::table('reservations')
                ->select('id')
                ->where('end_at', '<', Carbon::parse('2026-10-29 14:11:31', 'Europe/Amsterdam')))
            ->delete();
    }

    public function down(): void
    {
        // Deleted rows were bogus; nothing to restore.
    }
};
