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
        Schema::create('board_position_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_position_id')->constrained()->cascadeOnDelete();
            $table->timestamp('signed_at')->nullable();
            // Leaves room for a real e-signature backend (e.g. Google's
            // eSignature) later without another migration - not built now,
            // still undecided. Null/admin-recorded until then.
            $table->string('external_reference')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'board_position_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_position_signatures');
    }
};
