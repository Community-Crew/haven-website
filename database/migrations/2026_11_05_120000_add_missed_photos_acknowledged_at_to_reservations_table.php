<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the resident dismissed the "you didn't upload photos" popup for this
 * reservation, so it's shown once rather than on every app open. See
 * Reservation::scopeMissingPhotos().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('missed_photos_acknowledged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('missed_photos_acknowledged_at');
        });
    }
};
