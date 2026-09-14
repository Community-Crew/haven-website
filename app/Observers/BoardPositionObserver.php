<?php

namespace App\Observers;

use App\Models\BoardPosition;
use App\Services\Keycloak\KeycloakAdminService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Auto-provisions the Spatie role (+ matching hierarchical Keycloak group)
 * for a commission-scoped BoardPosition, so it doesn't have to be hand-
 * picked like a global position's shield_role_id is in BoardPositionForm.
 */
class BoardPositionObserver
{
    public function created(BoardPosition $position): void
    {
        // Global positions have their role picked directly by an admin in
        // the form; nothing to auto-provision. Same if shield_role_id was
        // already set some other way (tinker/seeder).
        if (! $position->organisation_id || $position->shield_role_id) {
            return;
        }

        try {
            // Each segment's own internal word-dashes (from a multi-word
            // slug, e.g. "advies-comissie", or a multi-word position name
            // like "Garden Coordinator") are swapped for underscores so the
            // flattened role name reads as clearly dash-separated segments
            // - "commission-advies_comissie-lid" - instead of an ambiguous
            // run of dashes. Applied before the path is built, so it lands
            // in the real Keycloak group names too, not just the role name
            // - both are flattened identically by the plain '/' -> '-'
            // replace below, which must stay symmetric with
            // KeycloakAdminController::callback()'s flattening of a path
            // read back from a Keycloak login.
            $orgSegment = str_replace('-', '_', $position->organisation->slug);
            $positionSegment = str_replace('-', '_', Str::slug($position->name));
            $path = "commission/{$orgSegment}/{$positionSegment}";

            $group = app(KeycloakAdminService::class)->findOrCreateGroupPath($path);

            // keycloak_group_id must be passed as a *creation* value here,
            // not set via a follow-up save() - RoleObserver::created() fires
            // synchronously during this call, and its guard against
            // creating a redundant flat group only works if the id is
            // already present on the model by then.
            $role = Role::firstOrCreate(
                ['name' => str_replace('/', '-', $path)],
                ['keycloak_group_id' => $group['id']]
            );

            // Covers the edge case of the role having already existed
            // without a group linked (e.g. created by hand before this
            // position was).
            if (! $role->keycloak_group_id) {
                $role->keycloak_group_id = $group['id'];
                $role->saveQuietly();
            }

            $position->shield_role_id = $role->id;
            $position->saveQuietly();
        } catch (Throwable $e) {
            Log::warning('Keycloak role provisioning failed for commission board position', [
                'board_position' => $position->name,
                'organisation_id' => $position->organisation_id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Keycloak sync failed')
                ->body("Board position \"{$position->name}\" was saved, but provisioning its role/Keycloak group failed. Check the logs.")
                ->danger()
                ->send();
        }
    }
}
