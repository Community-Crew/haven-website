<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /**
     * "Possibly moved out" is the review list for residents who left without
     * a Vestide import catching it (see User::scopePossiblyMovedOut) - an
     * admin confirms each with "Mark as moved out", which emails them.
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'residents' => Tab::make('Residents')
                ->modifyQueryUsing(fn (Builder $query) => $query->currentResidents()),
            'possibly_moved_out' => Tab::make('Possibly moved out')
                ->icon('heroicon-m-question-mark-circle')
                ->badge(fn () => User::possiblyMovedOut()->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->possiblyMovedOut()),
            'moved_out' => Tab::make('Moved out')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('moved_out_at')->whereNull('anonymized_at')),
            'anonymized' => Tab::make('Anonymized')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('anonymized_at')),
        ];
    }
}
