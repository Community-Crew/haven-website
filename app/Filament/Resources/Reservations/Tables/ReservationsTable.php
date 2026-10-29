<?php

namespace App\Filament\Resources\Reservations\Tables;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\Reservation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
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
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('room.name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable()
                    ->url(fn (Reservation $record) => $record->user && UserResource::canView($record->user)
                        ? UserResource::getUrl('view', ['record' => $record->user])
                        : null),
                TextColumn::make('start_at')
                    ->dateTime('d/m/y H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('End at')
                    ->dateTime('H:i'),
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
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->searchable(),
                TextColumn::make('organisation.name')
                    ->searchable(),
                // Lets an admin scanning the list see whether a reservation
                // has been checked in/out and whether it's had any notes
                // added, without opening every record - see [[NotesRelationManager]]
                // and [[ChecksRelationManager]] for where that detail lives.
                TextColumn::make('checkIn.verification_status')
                    ->label('Check-in')
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->placeholder('-'),
                TextColumn::make('checkOut.verification_status')
                    ->label('Check-out')
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->placeholder('-'),
                TextColumn::make('notes_count')
                    ->label('Notes')
                    ->counts('notes')
                    ->placeholder('0'),
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
