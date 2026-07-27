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
        Schema::create('reservation_policy_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('day_of_week');
            $table->integer('start_time');
            $table->integer('end_time');
            $table->foreignId('reservation_policy_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_policy_entries');
    }
};
