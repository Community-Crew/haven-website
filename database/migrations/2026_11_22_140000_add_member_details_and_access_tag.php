<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // text: both are stored encrypted, see User::$casts.
            $table->text('phone')->nullable();
            $table->text('birth_date')->nullable();
            // Keyed hash of the tag's UID, see User::hashAccessTag().
            $table->string('access_tag_hash', 64)->nullable()->unique();
        });

        Schema::table('memberships', function (Blueprint $table) {
            // Only that the board checked an ID - never a copy of the ID.
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('identity_verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('identity_verified_by');
            $table->dropColumn('identity_verified_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['access_tag_hash']);
            $table->dropColumn(['phone', 'birth_date', 'access_tag_hash']);
        });
    }
};
