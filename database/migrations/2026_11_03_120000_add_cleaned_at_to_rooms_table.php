<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When a room was last cleaned (by Vestide, from their panel) - newer than
 * the last reservation's check-out, it overrides how that reservation left
 * the room. See App\Filament\Vestide\Pages\RoomStatuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->timestamp('cleaned_at')->nullable();
            $table->foreignId('cleaned_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cleaned_by');
            $table->dropColumn('cleaned_at');
        });
    }
};
