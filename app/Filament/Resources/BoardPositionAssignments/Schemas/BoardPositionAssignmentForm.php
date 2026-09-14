<?php

namespace App\Filament\Resources\BoardPositionAssignments\Schemas;

use App\Models\BoardPositionAssignment;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;

class BoardPositionAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    // Part of the (user_id, board_position_id) pair a given
                    // active row is unique on - changing either after
                    // creation would just orphan the record instead of
                    // "moving" an assignment, so both are create-only.
                    ->disabledOn('edit'),
                ...self::positionComponents(),
            ]);
    }

    /**
     * Shared by this form and the Users relation manager (where the user is
     * fixed via the relationship and not itself a field) - the board
     * position pick plus is_public/sort_order/status, none of which depend
     * on how user_id was set. $userId lets the relation manager pass the
     * owner record's id directly instead of reading it via $get('user_id').
     *
     * @return array<int, Select|Toggle|TextInput|TextEntry|Actions>
     */
    public static function positionComponents(?int $userId = null): array
    {
        return [
            Select::make('board_position_id')
                ->label('Board position')
                ->relationship('boardPosition', 'name', fn ($query) => $query->orderBy('sort_order'))
                ->searchable()
                ->preload()
                ->required()
                ->disabledOn('edit')
                // A user can hold several different positions at once
                // (that's the whole point of this table existing
                // separately from Membership) - just not the same one
                // twice while it's still active.
                ->rule(function (Get $get, ?BoardPositionAssignment $record) use ($userId) {
                    return function (string $attribute, $value, Closure $fail) use ($get, $record, $userId) {
                        $resolvedUserId = $userId ?? $get('user_id');

                        if (! $resolvedUserId || ! $value) {
                            return;
                        }

                        $alreadyHeld = BoardPositionAssignment::query()
                            ->where('user_id', $resolvedUserId)
                            ->where('board_position_id', $value)
                            ->whereNull('ended_at')
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($alreadyHeld) {
                            $fail('This member already actively holds this position.');
                        }
                    };
                }),
            Toggle::make('is_public')
                ->label('Show on public board page')
                ->default(true),
            TextInput::make('sort_order')
                ->label('Display order')
                ->numeric()
                ->default(0),
            TextEntry::make('assignment_status')
                ->label('Status')
                ->state(fn (?BoardPositionAssignment $record) => $record?->ended_at
                    ? '🔴 Ended on '.$record->ended_at->format('Y-m-d H:i')
                    : '🟢 Active'
                ),
            Actions::make([
                Action::make('end_assignment')
                    ->label('End Assignment')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->size(Size::Small)
                    ->requiresConfirmation()
                    ->modalDescription('The position\'s role (and its Keycloak group membership) is revoked immediately - reinstating later grants it again.')
                    ->hidden(fn (?BoardPositionAssignment $record) => $record?->ended_at !== null)
                    ->action(function (?BoardPositionAssignment $record) {
                        $record->update(['ended_at' => now()]);

                        Notification::make()
                            ->title('Assignment ended')
                            ->warning()
                            ->send();
                    }),
                Action::make('reinstate')
                    ->label('Reinstate')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->size(Size::Small)
                    ->hidden(fn (?BoardPositionAssignment $record) => $record?->ended_at === null)
                    ->action(function (?BoardPositionAssignment $record) {
                        $record->update(['ended_at' => null]);

                        Notification::make()
                            ->title('Assignment reinstated')
                            ->success()
                            ->send();
                    }),
            ])->columnSpanFull(),
        ];
    }
}
