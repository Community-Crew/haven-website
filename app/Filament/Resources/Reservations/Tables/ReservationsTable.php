<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Enums\CheckVerificationStatus;
use App\Enums\ReservationStatus;
use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['room', 'user', 'organisation', 'checks']))
            ->columns([
                TextColumn::make('name')
                    // Hints the booking was scripted - see Reservation::bookingSignals().
                    ->icon(fn (Reservation $record) => match (true) {
                        isset($record->bookingSignals()['fast']) => 'heroicon-o-bolt',
                        $record->bookingSignals() !== [] => 'heroicon-o-clock',
                        default => null,
                    })
                    ->iconColor(fn (Reservation $record) => isset($record->bookingSignals()['fast']) ? 'danger' : 'warning')
                    ->tooltip(fn (Reservation $record) => implode("\n", $record->bookingSignals()) ?: null)
                    ->searchable(),
                TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Resident')
                    ->searchable()
                    ->url(fn (Reservation $record) => $record->user && UserResource::canView($record->user)
                        ? UserResource::getUrl('view', ['record' => $record->user])
                        : null),
                TextColumn::make('start_at')
                    ->label('When')
                    ->dateTime('d/m/y H:i')
                    ->description(fn (Reservation $record) => 'until '.$record->end_at->copy()->setTimezone(FilamentTimezone::get())->format('H:i'))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->searchable(),
                // One answer for both check-in and check-out; the detail is
                // on the reservation, see [[ChecksRelationManager]].
                TextColumn::make('photos')
                    ->label('Photos')
                    ->state(fn (Reservation $record): ?string => self::photoStatus($record))
                    ->placeholder('-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'To review' => 'warning',
                        'Rejected' => 'danger',
                        'Verified' => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('share_user')
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
                TextColumn::make('organisation.name')
                    ->label('Organisation')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes_count')
                    ->label('Notes')
                    ->counts('notes')
                    ->placeholder('0')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(fn () => collect(ReservationStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
                        ->all()),
                SelectFilter::make('room_id')
                    ->label('Room')
                    ->relationship('room', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user_id')
                    ->label('Resident')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('booked_suspiciously_fast')
                    ->label('Booked suspiciously fast')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->bookedSuspiciouslyFast()),
                Filter::make('start_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('start_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'From '.$data['from'];
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Until '.$data['until'];
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                Action::make('reviewPhoto')
                    ->label('Review')
                    ->icon('heroicon-m-camera')
                    ->color('warning')
                    ->visible(fn (Reservation $record) => ReservationCheckResource::canViewAny() && self::pendingCheck($record) !== null)
                    ->url(fn (Reservation $record) => ReservationCheckResource::getUrl('review', ['check' => self::pendingCheck($record)?->id])),
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function pendingCheck(Reservation $record): ?ReservationCheck
    {
        return $record->checks->firstWhere('verification_status', CheckVerificationStatus::PENDING);
    }

    protected static function photoStatus(Reservation $record): ?string
    {
        $statuses = $record->checks->pluck('verification_status');

        return match (true) {
            $statuses->isEmpty() => null,
            $statuses->contains(CheckVerificationStatus::PENDING) => 'To review',
            $statuses->contains(CheckVerificationStatus::REJECTED) => 'Rejected',
            $statuses->count() >= 2 => 'Verified',
            default => 'Checked in',
        };
    }
}
