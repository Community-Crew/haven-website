<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rows are never edited: a change is a new version, so what someone
        // accepted stays readable.
        Schema::create('membership_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->longText('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'id']);
        });

        Schema::table('memberships', function (Blueprint $table) {
            // The board picks the type when approving an application.
            $table->foreignId('member_type_id')->nullable()->change();
            $table->foreignId('statutes_document_id')->nullable()->constrained('membership_documents')->restrictOnDelete();
            $table->foreignId('rules_document_id')->nullable()->constrained('membership_documents')->restrictOnDelete();
            $table->timestamp('documents_accepted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('statutes_document_id');
            $table->dropConstrainedForeignId('rules_document_id');
            $table->dropColumn('documents_accepted_at');
        });

        Schema::dropIfExists('membership_documents');
    }
};
