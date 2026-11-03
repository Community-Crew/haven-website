<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How long the proof-of-work challenge had been out when the reservation was
 * made - roughly the time spent on the booking page. Null for reservations
 * made without one (admin-created, or from before the proof of work). See
 * Reservation::bookingSignals().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unsignedInteger('challenge_age_seconds')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('challenge_age_seconds');
        });
    }
};
