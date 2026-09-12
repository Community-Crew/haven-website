<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Commission-level default NDA text, used by any of its
        // requires_nda positions that don't set their own - see
        // BoardPosition::ndaText().
        Schema::table('organisations', function (Blueprint $table) {
            $table->text('nda_text')->nullable();
        });

        // Per-position override of the commission's default (or the only
        // text at all, for a global position with no organisation).
        Schema::table('board_positions', function (Blueprint $table) {
            $table->text('nda_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('nda_text');
        });

        Schema::table('board_positions', function (Blueprint $table) {
            $table->dropColumn('nda_text');
        });
    }
};
