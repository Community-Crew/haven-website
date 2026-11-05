<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The resident app's FAQ, edited in Filament. Same language split as
 * privacy_policies: the plain column is Dutch, the `_en` one an optional
 * English translation the app falls back from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('icon', 16)->nullable();
            // Steps of the flowchart shown above the questions, if any - see
            // FaqCategory for the shape.
            $table->json('flowchart')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('faq_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->string('question_en')->nullable();
            // Rich text (HTML) from Filament's editor, for links and lists.
            $table->text('answer');
            $table->text('answer_en')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_questions');
        Schema::dropIfExists('faq_categories');
    }
};
