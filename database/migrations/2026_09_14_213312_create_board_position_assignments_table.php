<?php

use App\Enums\MembershipStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A board position is held by a user, not by any one Membership stretch
     * - a user can hold several simultaneously (chair of one commission,
     * treasurer of the board, coordinator of another commission, ...),
     * which memberships.board_position_id (a single nullable FK on the one
     * open membership a user can have) couldn't represent. This decouples
     * position-holding into its own table - the same pattern
     * board_position_signatures already uses for a user+position fact that
     * doesn't belong to Membership.
     */
    public function up(): void
    {
        Schema::create('board_position_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_position_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            // Null while actively held - set (not deleted) when revoked, so
            // "who held what, and when" stays on record. Mirrors
            // board_position_signatures.signed_at's mark/unmark pattern.
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        // Backfill: memberships.board_position_id/is_public/sort_order
        // already held live data (this shipped to production in v1.3.0) -
        // carry it over instead of silently dropping it.
        $openStatuses = array_map(fn (MembershipStatus $status) => $status->value, MembershipStatus::open());

        DB::table('memberships')
            ->whereNotNull('board_position_id')
            ->get()
            ->each(function ($membership) use ($openStatuses) {
                DB::table('board_position_assignments')->insert([
                    'user_id' => $membership->user_id,
                    'board_position_id' => $membership->board_position_id,
                    'is_public' => $membership->is_public,
                    'sort_order' => $membership->sort_order,
                    // Only a still-open membership counts as actively
                    // holding the position - an already-ended one is
                    // historical, so it's backfilled already-ended too.
                    'ended_at' => in_array($membership->status, $openStatuses, true)
                        ? null
                        : ($membership->updated_at ?? now()),
                    'created_at' => $membership->created_at ?? now(),
                    'updated_at' => $membership->updated_at ?? now(),
                ]);
            });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('board_position_id');
            $table->dropColumn(['is_public', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->foreignId('board_position_id')->nullable()->after('has_voting_rights')->constrained()->nullOnDelete();
            $table->boolean('is_public')->default(true)->after('board_position_id');
            $table->unsignedInteger('sort_order')->default(0)->after('is_public');
        });

        Schema::dropIfExists('board_position_assignments');
    }
};
