<?php

namespace App\Observers;

use App\Models\BoardPosition;
use App\Models\Organisation;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Seeds every new commission with its two starting board positions
 * (Voorzitter, Lid) so it isn't created empty - see BoardPositionObserver
 * for what happens next: each of these auto-provisions its own scoped
 * role/Keycloak group, since organisation_id is set on both.
 */
class OrganisationObserver
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_POSITIONS = ['Voorzitter', 'Lid'];

    public function created(Organisation $organisation): void
    {
        // External orgs (the landlord Vestide, etc.) live in the same
        // table but aren't commissions - no default positions/roles for
        // them.
        if (! $organisation->is_commission) {
            return;
        }

        try {
            foreach (self::DEFAULT_POSITIONS as $index => $name) {
                BoardPosition::create([
                    'name' => $name,
                    'organisation_id' => $organisation->id,
                    'sort_order' => $index,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Default board positions failed to seed for new commission', [
                'organisation' => $organisation->name,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Commission created, but default positions failed')
                ->body("\"{$organisation->name}\" was saved, but seeding its default Voorzitter/Lid positions failed. Check the logs and add them manually if needed.")
                ->danger()
                ->send();
        }
    }
}
