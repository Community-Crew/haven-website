<?php

namespace App\Filament\Resources\ReservationChecks\Tables;

use App\Enums\CheckVerificationStatus;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\ReservationCheck;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class ReservationChecksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_urls')
                    ->label('Photos')
                    ->square()
                    ->stacked()
                    ->limit(3),
                TextColumn::make('reservation.room.name')
                    ->label('Room')
                    ->searchable(),
                TextColumn::make('reservation.user.name')
                    ->label('Resident')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                TextColumn::make('occurred_at')
                    ->dateTime('d/m/y H:i')
                    ->sortable(),
                IconColumn::make('flag_reason')
                    ->label('Flagged')
                    ->icon(fn (?string $state) => $state ? 'heroicon-o-flag' : 'heroicon-o-minus')
                    ->color(fn (?string $state) => $state ? 'danger' : 'gray')
                    ->tooltip(fn (ReservationCheck $record) => $record->flag_reason),
                TextColumn::make('verification_status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn ($query) => $query
                ->with('photos')
                ->orderByRaw('flag_reason IS NULL')
                ->orderBy('created_at'))
            ->filters([
                SelectFilter::make('verification_status')
                    ->options(fn () => collect(CheckVerificationStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->visible(fn (ReservationCheck $record) => $record->verification_status !== CheckVerificationStatus::VERIFIED)
                    ->action(function (ReservationCheck $record) {
                        $record->update([
                            'verification_status' => CheckVerificationStatus::VERIFIED,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_note' => null,
                        ]);

                        Notification::make()->title('Marked as verified')->success()->send();
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('update')
                    ->schema([
                        Textarea::make('rejection_note')
                            ->label('Reason')
                            ->required(),
                    ])
                    ->visible(fn (ReservationCheck $record) => $record->verification_status !== CheckVerificationStatus::REJECTED)
                    ->action(function (ReservationCheck $record, array $data) {
                        $record->update([
                            'verification_status' => CheckVerificationStatus::REJECTED,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_note' => $data['rejection_note'],
                        ]);

                        Mail::to($record->reservation->user)->send(new ReservationCheckRejectedMail($record));

                        Notification::make()->title('Marked as rejected')->success()->send();
                    }),
            ]);
    }
}
