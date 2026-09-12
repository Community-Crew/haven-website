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
        Schema::table('organisations', function (Blueprint $table) {
            // Differentiates an actual commission (gets default board
            // positions + roles, shows in commission-facing UI) from an
            // external organisation stored in the same table - the
            // landlord (Vestide), or another outside party an AgendaItem/
            // Reservation might reference. Defaults true since every
            // existing row predates this distinction and was a commission.
            $table->boolean('is_commission')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->dropColumn('is_commission');
        });
    }
};
