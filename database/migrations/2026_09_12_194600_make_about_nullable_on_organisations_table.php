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
        // Was NOT NULL with no default since the original migration, which
        // never got exercised until CommissionResource became the first
        // Filament resource able to create organisations - CommissionForm
        // doesn't require it, so a blank submission 500'd on save.
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('about')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisations', function (Blueprint $table) {
            $table->string('about')->nullable(false)->change();
        });
    }
};
