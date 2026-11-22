<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proof of who accepted which rules, when. The text is copied in rather than
 * referenced, because admins can edit a room's rules at any time and the
 * record has to keep showing what was actually accepted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_rule_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('room_id')->constrained();
            $table->string('moment');
            $table->char('rules_hash', 64);
            $table->text('default_rules')->nullable();
            $table->text('default_rules_en')->nullable();
            $table->text('rules')->nullable();
            $table->text('rules_en')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();

            $table->unique(['reservation_id', 'moment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_rule_acceptances');
    }
};
