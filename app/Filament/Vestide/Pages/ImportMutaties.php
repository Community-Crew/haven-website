<?php

namespace App\Filament\Vestide\Pages;

use App\Filament\Pages\ImportVestideMutaties;

/**
 * The admin panel's mutaties import, offered to Vestide directly so they can
 * apply their own monthly export. Same page and handover; where it lives,
 * who may open it (User::canAccessPanel() here, not Shield) and the label
 * printing - which Vestide doesn't do - differ.
 */
class ImportMutaties extends ImportVestideMutaties
{
    protected static string|\UnitEnum|null $navigationGroup = null;

    protected bool $showsLabels = false;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return true;
    }
}
