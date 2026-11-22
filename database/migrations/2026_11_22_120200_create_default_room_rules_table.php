<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single-row table: the rules that apply to every room, such as taking the
 * check-in and check-out photos. Starts empty, so nothing changes for
 * residents until an admin fills them in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('default_room_rules', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable();
            $table->text('content_en')->nullable();
            $table->timestamps();
        });

        DB::table('default_room_rules')->insert([
            'id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('default_room_rules');
    }
};
