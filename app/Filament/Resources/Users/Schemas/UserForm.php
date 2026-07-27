<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\RegistrationCode;
use App\Models\Unit;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('keycloak_id')
                    ->required(),
                Select::make('unit_id')
                    ->relationship(name: 'unit', titleAttribute: 'id')
                    ->getOptionLabelFromRecordUsing(fn (Unit $record) => $record->name)
                    ->searchable()
                    ->preload(),
                TextEntry::make('activation_status')
                    ->label('Status')
                    ->state(fn ($record) => $record?->activated_at
                        ? '🟢 Activated on '.$record->activated_at->format('Y-m-d H:i')
                        : '🔴 Not Activated yet'
                    ),
                Actions::make([
                    Action::make('validate_user')
                        ->label('Activate Account')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->size(Size::Small)
                        ->hidden(fn ($record) => $record?->activated_at !== null)
                        ->action(function ($record) {
                            $record->update(['activated_at' => now()]);

                            if ($record->unit_id) {
                                RegistrationCode::where('unit_id', $record->unit_id)
                                    ->update(['is_used' => true]);
                            }

                            Notification::make()
                                ->title('User account activated successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('unvalidate_user')
                        ->label('Deactivate / Unvalidate')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->size(Size::Small)
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate User Profile')
                        ->modalDescription('Are you sure you want to unvalidate this user? This will clear their activated timestamp and open their physical unit back up.')
                        ->hidden(fn ($record) => $record?->activated_at === null)
                        ->action(function ($record) {
                            $record->update(['activated_at' => null]);

                            Notification::make()
                                ->title('User account deactivated')
                                ->warning()
                                ->send();
                        }),
                ])->columnSpanFull(),
            ]);
    }
}
