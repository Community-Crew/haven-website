<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\RegistrationCode;
use App\Models\Unit;
use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Models\Role;

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
                TextInput::make('phone')
                    ->label('Phone number')
                    ->tel()
                    // Hidden attributes don't reach the form by themselves,
                    // and saving an empty field would wipe the value.
                    ->formatStateUsing(fn (?User $record) => $record?->phone)
                    ->rule('phone:INTERNATIONAL,NL')
                    ->dehydrateStateUsing(fn (?string $state) => filled($state)
                        ? (new PhoneNumber($state, 'NL'))->formatE164()
                        : null),
                DatePicker::make('birth_date')
                    ->formatStateUsing(fn (?User $record) => $record?->birth_date)
                    ->maxDate(now()->subDay()),
                // Only the hash is stored, so this starts empty: leaving it
                // empty keeps the current tag, scanning a new one replaces it.
                TextInput::make('access_tag_hash')
                    ->label('Tag / key')
                    ->placeholder(fn ($record) => $record?->access_tag_hash ? 'Linked - scan to replace' : 'Scan tag or key')
                    ->formatStateUsing(fn () => null)
                    ->autocomplete(false)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => User::hashAccessTag($state))
                    ->rule(fn (?User $record) => function (string $attribute, $value, Closure $fail) use ($record) {
                        $taken = User::where('access_tag_hash', User::hashAccessTag($value))
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($taken) {
                            $fail('This tag is already linked to another user.');
                        }
                    })
                    ->hintAction(
                        Action::make('unlink_access_tag')
                            ->label('Unlink')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->visible(fn (?User $record) => $record?->access_tag_hash !== null)
                            ->action(fn (User $record) => $record->update(['access_tag_hash' => null]))
                    ),
                TextInput::make('keycloak_id')
                    ->required(),
                Select::make('unit_id')
                    ->relationship(name: 'unit', titleAttribute: 'id')
                    ->getOptionLabelFromRecordUsing(fn (Unit $record) => $record->name)
                    ->searchable()
                    ->preload(),
                // Deliberately not ->relationship('roles', ...) - that
                // auto-syncs the pivot directly on save, bypassing
                // UserRoleSyncService entirely (and so skipping the
                // Keycloak group add/remove it does). Plain ->options() +
                // ->dehydrated(false) instead: EditUser::afterSave() diffs
                // this against the record's original roles and calls
                // addRole()/removeRole() per change.
                Select::make('role_ids')
                    ->label('Roles')
                    ->multiple()
                    ->options(fn () => Role::pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->dehydrated(false)
                    ->default(fn ($record) => $record?->roles->pluck('id')->all())
                    ->helperText(fn ($record) => $record && ! $record->keycloak_id
                        ? 'This user has no Keycloak account yet - role changes will only apply locally.'
                        : null),
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
