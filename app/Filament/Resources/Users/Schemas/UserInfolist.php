<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('keycloak_id'),
                TextEntry::make('unit')
                    ->label('Unit')
                    ->placeholder('No unit assigned')
                    ->formatStateUsing(fn ($state) => $state?->name),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('activation_status')
                    ->label('Account Status')
                    ->state(fn ($record) => $record?->activated_at ? 'Activated' : 'Not Activated yet')
                    ->badge()
                    ->color(fn ($record) => $record?->activated_at ? 'success' : 'danger')
                    ->hintAction(
                        fn ($record) => $record?->activated_at === null
                            ? Action::make('activate')
                                ->label('Activate Now')
                                ->icon('heroicon-m-check-circle')
                                ->color('success')
                                ->action(function ($record) {
                                    $record->update(['activated_at' => now()]);
                                })
                            : Action::make('deactivate')
                                ->label('Deactivate')
                                ->icon('heroicon-m-x-circle')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->action(function ($record) {
                                    $record->update(['activated_at' => null]);
                                })
                    ),
            ]);
    }
}
