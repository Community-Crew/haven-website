<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Encrypted, see User::$casts. Former residents have no unit, and
            // a SEPA mandate needs the debtor's address.
            $table->text('postal_address')->nullable();
        });

        Schema::table('member_types', function (Blueprint $table) {
            $table->boolean('is_beneficiary')->default(false);
            $table->boolean('is_honorary')->default(false);
        });

        $this->ensureType('Begunstiger', 'is_beneficiary', 'Oud-bewoner die Haven blijft steunen.', 100);
        $this->ensureType('Erelid', 'is_honorary', 'Door het bestuur benoemd erelid.', 110);
    }

    /**
     * Voting rights start off: the board sets them to match the statutes.
     */
    private function ensureType(string $name, string $flag, string $description, int $sortOrder): void
    {
        if (DB::table('member_types')->where('name', $name)->update([$flag => true]) > 0) {
            return;
        }

        DB::table('member_types')->insert([
            'name' => $name,
            'description' => $description,
            'has_voting_rights' => false,
            'is_active' => true,
            $flag => true,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('member_types', function (Blueprint $table) {
            $table->dropColumn(['is_beneficiary', 'is_honorary']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('postal_address');
        });
    }
};
