<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\MembershipStatus;
use App\Filament\Support\MembershipReviewActions;
use App\Models\RegistrationCode;
use App\Models\User;
use App\Services\ResidentOffboardingService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['unit', 'currentMembership.memberType']))
            ->columns([
                TextColumn::make('name')
                    ->description(fn (User $record): ?string => $record->email)
                    ->searchable(['name', 'email']),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('next_step')
                    ->label('Next step')
                    ->visible(fn ($livewire) => self::onApplicationsTab($livewire))
                    ->state(fn (User $record): string => MembershipReviewActions::isReady($record->currentMembership)
                        ? 'Ready for ID check'
                        : 'Waiting for member')
                    ->badge()
                    ->color(fn (User $record): string => MembershipReviewActions::isReady($record->currentMembership) ? 'warning' : 'gray'),
                TextColumn::make('currentMembership.created_at')
                    ->label('Applied on')
                    ->date('d-m-Y')
                    ->visible(fn ($livewire) => self::onApplicationsTab($livewire)),
                TextColumn::make('status')
                    ->label('Status')
                    ->hidden(fn ($livewire) => self::onApplicationsTab($livewire))
                    ->state(fn (User $record): string => self::statusLabel($record))
                    ->description(fn (User $record): ?string => $record->moved_out_at && ! $record->anonymized_at && ! self::isKeptAfterMoveOut($record)
                        ? 'Data removed '.$record->anonymizesAt()->format('d/m/Y')
                        : null)
                    ->badge()
                    ->color(fn (User $record): string => self::statusColor($record)),
                TextColumn::make('currentMembership.memberType.name')
                    ->label('Member type')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('id_checked')
                    ->label('ID checked')
                    ->state(fn (User $record): bool => $record->currentMembership?->identity_verified_at !== null)
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label('Phone number')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_access_tag')
                    ->label('Tag')
                    ->state(fn (User $record): bool => $record->access_tag_hash !== null)
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Kind of member')
                    ->options([
                        'regular' => 'Member',
                        'honorary' => 'Erelid',
                        'beneficiary' => 'Begunstiger',
                    ])
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('memberships', fn (Builder $membership) => $membership
                            ->whereIn('status', [MembershipStatus::ACTIVE, MembershipStatus::SUSPENDED])
                            ->whereHas('memberType', fn (Builder $type) => $type
                                ->where('is_honorary', $data['value'] === 'honorary')
                                ->where('is_beneficiary', $data['value'] === 'beneficiary')))
                        : $query),
                // Anonymized accounts are history: out of sight unless asked for.
                SelectFilter::make('anonymized')
                    ->label('Anonymized accounts')
                    ->placeholder('Hide')
                    ->options([
                        'include' => 'Show too',
                        'only' => 'Show only these',
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'include' => $query,
                        'only' => $query->whereNotNull('anonymized_at'),
                        default => $query->whereNull('anonymized_at'),
                    }),
                SelectFilter::make('membership')
                    ->label('Membership')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn (MembershipStatus $status) => [$status->value => $status->getLabel()])
                        ->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('memberships', fn (Builder $membership) => $membership->where('status', $data['value']))
                        : $query),
            ])
            ->recordActions([
                MembershipReviewActions::approve(),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    MembershipReviewActions::appointHonorary(),
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

                    // What can't easily be undone comes last.
                    MembershipReviewActions::reject(),
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
                ]),
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

    /**
     * The table is also used on the Units relation manager, which has no tabs.
     */
    protected static function onApplicationsTab(mixed $livewire): bool
    {
        return ($livewire->activeTab ?? null) === 'applications';
    }

    /**
     * One answer to "who is this?", instead of separate membership, type and
     * account columns.
     */
    protected static function statusLabel(User $record): string
    {
        $membership = $record->currentMembership;

        if ($record->anonymized_at) {
            return 'Anonymized';
        }

        if ($membership?->status === MembershipStatus::PENDING) {
            return 'Applied';
        }

        if ($membership) {
            $kind = match (true) {
                (bool) $membership->memberType?->is_honorary => 'Erelid',
                (bool) $membership->memberType?->is_beneficiary => 'Begunstiger',
                default => 'Member',
            };

            return $membership->status === MembershipStatus::SUSPENDED ? "{$kind} (suspended)" : $kind;
        }

        return match (true) {
            $record->moved_out_at !== null => 'Moved out',
            $record->activated_at !== null => 'Resident',
            default => 'Not activated',
        };
    }

    /**
     * Mostly quiet: colour is for members and for what needs attention.
     */
    protected static function statusColor(User $record): string
    {
        $membership = $record->currentMembership;

        return match (true) {
            $record->anonymized_at !== null, $membership === null => 'gray',
            $membership->status === MembershipStatus::ACTIVE => 'success',
            default => 'warning',
        };
    }

    protected static function isKeptAfterMoveOut(User $record): bool
    {
        return $record->moved_out_at !== null
            && (bool) $record->currentMembership?->memberType?->keepsDataAfterMoveOut();
    }

    protected static function movedOutDescription(): string
    {
        return sprintf(
            'They lose access, open memberships and board positions end, and they get an email saying their account and personal data will be anonymized in %d days. Until then this can be undone.',
            ResidentOffboardingService::RETENTION_DAYS,
        );
    }
}
