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
        Schema::table('reservation_policy_room', function (Blueprint $table) {
            $table->dropForeign(['reservation_policy_id']);

            $table->foreign('reservation_policy_id')
                ->references('id')
                ->on('reservation_policies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservation_policy_room', function (Blueprint $table) {
            $table->dropForeign(['reservation_policy_id']);

            $table->foreign('reservation_policy_id')
                ->references('id')
                ->on('reservation_policies');
        });
    }
};
