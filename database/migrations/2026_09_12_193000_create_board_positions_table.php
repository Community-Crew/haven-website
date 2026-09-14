<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_positions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Null = a global position (Chair, Secretary, Treasurer). Set =
            // that commission's own position, scoped to it. Cascades on
            // delete rather than nulling out - an org-scoped position is
            // meaningless without its org, so it shouldn't get silently
            // promoted to global by losing its organisation_id.
            $table->foreignId('organisation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('shield_role_id')->nullable()->constrained('roles')->nullOnDelete(); // Explicitly targets Spatie's roles table
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('requires_nda')->default(false);
            $table->timestamps();

            $table->unique(['name', 'organisation_id']);
        });

        // Postgres/SQLite both treat NULL as distinct in a unique index, so
        // the composite unique above doesn't actually stop two global
        // (organisation_id null) positions sharing a name - a partial index
        // covers that gap for the null case specifically.
        DB::statement('create unique index board_positions_name_unique_when_global on board_positions (name) where organisation_id is null');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_positions');
    }
};
