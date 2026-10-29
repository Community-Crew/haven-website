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
        Schema::create('reservation_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->foreignId('user_id')->constrained();
            // Server-set at submission time, deliberately never client input
            // - the flagging pass below compares this against the
            // reservation's start/end window, so it must be trustworthy.
            $table->dateTime('occurred_at');
            $table->string('verification_status')->default('pending');
            $table->text('flag_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();

            $table->unique(['reservation_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_checks');
    }
};
