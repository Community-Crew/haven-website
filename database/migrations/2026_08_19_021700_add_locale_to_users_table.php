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
        Schema::table('users', function (Blueprint $table) {
            // Defaults to 'nl' - Haven is a Dutch community and most members
            // are Dutch-speaking. Explicit column (rather than deriving from
            // Keycloak/Accept-Language on every mail) so it's a stable,
            // user-settable preference.
            $table->string('locale', 2)->default('nl')->after('activated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
