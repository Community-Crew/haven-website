<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reservation_policies', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn(['weekly_schedule', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_policies', function (Blueprint $table) {
            $table->json('weekly_schedule')->nullable();
            $table->foreignId('room_id')->constrained();
        });
    }
};
