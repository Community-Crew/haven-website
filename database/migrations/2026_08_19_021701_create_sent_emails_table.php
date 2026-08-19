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
        // A record of every mail the app has sent - not the audit log (which
        // tracks model changes), this tracks outbound mail specifically, for
        // support ("did they actually get their activation email?") and as
        // the foundation for future mailing-list/broadcast sends.
        Schema::create('sent_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mailable');
            $table->string('to');
            $table->string('subject');
            $table->string('locale', 2)->nullable();
            $table->timestamp('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_emails');
    }
};
