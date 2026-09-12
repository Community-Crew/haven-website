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
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn('board_role');

            $table->foreignId('board_position_id')
                ->nullable()
                ->after('has_voting_rights')
                ->constrained()
                ->nullOnDelete();

            // Lets an admin keep a position on record without publishing it
            // (e.g. still gathering signatures/permissions before it's
            // announced) - see the public board endpoint, Part H.
            $table->boolean('is_public')->default(true)->after('board_position_id');

            // Org-chart display order among public position holders.
            $table->unsignedInteger('sort_order')->default(0)->after('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('board_position_id');
            $table->dropColumn(['is_public', 'sort_order']);

            $table->string('board_role')->nullable();
        });
    }
};
