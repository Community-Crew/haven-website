<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\RegistrationCode;
use App\Models\User;
use App\Services\ResidentOffboardingService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Throwable;

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
                    ->state(fn (User $record): string => match (true) {
                        $record->anonymized_at !== null => 'Anonymized',
                        $record->moved_out_at !== null => 'Moved out',
                        $record->activated_at !== null => 'Activated',
                        default => 'Not Activated yet',
                    })
                    ->description(fn (User $record): ?string => $record->moved_out_at && ! $record->anonymized_at
                        ? 'Data removed '.$record->anonymizesAt()->format('d/m/Y')
                        : null)
                    ->badge()
                    ->color(fn (User $record): string => match (true) {
                        $record->anonymized_at !== null => 'gray',
                        $record->moved_out_at !== null => 'warning',
                        $record->activated_at !== null => 'success',
                        default => 'danger',
                    }),
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
                    ->visible(fn ($record) => $record?->activated_at === null && ! $record?->isFormerResident())
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

                Action::make('markMovedOut')
                    ->label('Mark as moved out')
                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(self::movedOutDescription())
                    ->authorize('update')
                    ->visible(fn (User $record) => ! $record->isFormerResident())
                    ->action(function (User $record, ResidentOffboardingService $offboarding) {
                        if ($offboarding->markMovedOut($record)) {
                            $offboarding->sendMovedOutNotice($record);
                        }

                        Notification::make()
                            ->title('Marked as moved out')
                            ->body('They have been emailed. Their data will be anonymized on '.$record->anonymizesAt()->format('d/m/Y').'.')
                            ->success()
                            ->send();
                    }),

                Action::make('undoMoveOut')
                    ->label('Undo move-out')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Cancels the scheduled anonymization. It does not re-activate them - give them a registration code or use "Activate Now".')
                    ->authorize('update')
                    ->visible(fn (User $record) => $record->moved_out_at !== null && $record->anonymized_at === null)
                    ->action(function (User $record, ResidentOffboardingService $offboarding) {
                        $offboarding->undoMoveOut($record);

                        Notification::make()->title('Move-out undone')->success()->send();
                    }),

                Action::make('anonymizeNow')
                    ->label('Anonymize now')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anonymize this user now?')
                    ->modalDescription('For erasure requests. Deletes their Keycloak account and permanently wipes their name, email, check-in/out photos, notes, sent-mail and audit history. Reservations stay as anonymous statistics. This cannot be undone.')
                    ->modalSubmitActionLabel('Anonymize permanently')
                    ->authorize('delete')
                    ->visible(fn (User $record) => $record->anonymized_at === null)
                    ->action(function (User $record, ResidentOffboardingService $offboarding) {
                        try {
                            $offboarding->anonymize($record);
                        } catch (Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title('Could not anonymize')
                                ->body('Deleting the Keycloak account failed, so nothing was changed. Try again later.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()->title('User anonymized')->success()->send();
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
                    BulkAction::make('markMovedOut')
                        ->label('Mark as moved out')
                        ->icon('heroicon-m-arrow-right-start-on-rectangle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription(self::movedOutDescription())
                        ->authorizeIndividualRecords('update')
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, ResidentOffboardingService $offboarding) {
                            $marked = $records->filter(fn (User $user) => $offboarding->markMovedOut($user));
                            $marked->each(fn (User $user) => $offboarding->sendMovedOutNotice($user));

                            Notification::make()
                                ->title("{$marked->count()} marked as moved out")
                                ->body('Each has been emailed the move-out notice.')
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function movedOutDescription(): string
    {
        return sprintf(
            'They lose access, open memberships and board positions end, and they get an email saying their account and personal data will be anonymized in %d days. Until then this can be undone.',
            ResidentOffboardingService::RETENTION_DAYS,
        );
    }
}
