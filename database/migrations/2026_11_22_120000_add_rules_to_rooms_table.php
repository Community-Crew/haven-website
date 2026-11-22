<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-room house rules residents accept at booking and again at check-in.
 * `rules` (Dutch) is leading, `rules_en` a courtesy translation. A room
 * without rules asks for no acceptance, so this changes nothing until an
 * admin fills them in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->text('rules')->nullable();
            $table->text('rules_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['rules', 'rules_en']);
        });
    }
};
