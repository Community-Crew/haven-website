<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MailCircuitBreaker counts recent rows (overall and per recipient) before
 * every send.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sent_emails', function (Blueprint $table) {
            $table->index('sent_at');
            $table->index(['to', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sent_emails', function (Blueprint $table) {
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['to', 'sent_at']);
        });
    }
};
