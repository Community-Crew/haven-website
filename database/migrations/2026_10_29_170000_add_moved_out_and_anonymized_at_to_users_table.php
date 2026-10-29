<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * moved_out_at: set when a resident is marked as having left (Vestide import
 * or an admin) - starts the retention period, after which
 * app:anonymize-former-residents wipes their personal data.
 * anonymized_at: set once that has happened.
 *
 * No backfill: residents deactivated before this existed are surfaced on the
 * Users list's "Possibly moved out" tab for an admin to confirm, so each one
 * gets the move-out notice rather than being silently scheduled for deletion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('moved_out_at')->nullable()->index();
            $table->timestamp('anonymized_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['moved_out_at', 'anonymized_at']);
        });
    }
};
