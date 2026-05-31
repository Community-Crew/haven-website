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
            $table->foreignId('shield_role_id')
                ->after('room_id')
                ->nullable()
                ->constrained('roles') // Explicitly targets Spatie's roles table
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_policies', function (Blueprint $table) {
            Schema::table('reservation_policies', function (Blueprint $table) {
                $table->dropForeign(['shield_role_id']);
                $table->dropColumn('shield_role_id');
            });
        });
    }
};
