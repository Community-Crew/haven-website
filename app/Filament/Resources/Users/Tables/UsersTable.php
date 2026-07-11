<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\RegistrationCode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activation_status')
                    ->label('Account Status')
                    ->state(fn ($record) => $record?->activated_at ? 'Activated' : 'Not Activated yet')
                    ->badge()
                    ->color(fn ($record) => $record?->activated_at ? 'success' : 'danger'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('activate')
                    ->label('Activate Now')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record?->activated_at === null)
                    ->action(function ($record) {
                        $record->update(['activated_at' => now()]);

                        if ($record->unit_id) {
                            RegistrationCode::where('unit_id', $record->unit_id)
                                ->update(['is_used' => true]);
                        }

                        Notification::make()
                            ->title('User account activated')
                            ->success()
                            ->send();
                    }),

                Action::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record?->activated_at !== null)
                    ->action(function ($record) {
                        $record->update(['activated_at' => null]);

                        if ($record->unit_id) {
                            RegistrationCode::where('unit_id', $record->unit_id)
                                ->update(['is_used' => false]);
                        }

                        Notification::make()
                            ->title('User account deactivated')
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
