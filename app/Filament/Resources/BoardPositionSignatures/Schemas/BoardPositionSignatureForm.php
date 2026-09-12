<?php

namespace App\Filament\Resources\BoardPositionSignatures\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;

class BoardPositionSignatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    // Part of the (user_id, board_position_id) unique pair -
                    // changing either after creation would just orphan the
                    // record instead of "moving" a signature, so both are
                    // create-only.
                    ->disabledOn('edit'),
                Select::make('board_position_id')
                    ->label('Board position')
                    ->relationship('boardPosition', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('edit'),
                TextInput::make('external_reference')
                    ->label('Signed document link')
                    ->helperText('Optional link to the signed document in Google Drive (Workspace eSignature has no API, so this is manually pasted, not auto-filled).'),
                TextEntry::make('signed_status')
                    ->label('Status')
                    ->state(fn ($record) => $record?->signed_at
                        ? '🟢 Signed on '.$record->signed_at->format('Y-m-d H:i')
                        : '🔴 Not signed yet'
                    ),
                Actions::make([
                    Action::make('mark_signed')
                        ->label('Mark as Signed')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->size(Size::Small)
                        ->hidden(fn ($record) => $record?->signed_at !== null)
                        ->action(function ($record) {
                            // No explicit re-grant needed: the role itself
                            // is always assigned regardless of NDA status
                            // (BoardPosition::grantRoleFor()) - only its
                            // permissions are gated, live, by
                            // User::hasPermissionViaRole()'s override
                            // consulting BoardPosition::ndaSatisfiedFor().
                            // Setting signed_at here is the whole effect;
                            // the very next permission check picks it up.
                            $record->update(['signed_at' => now()]);

                            Notification::make()
                                ->title('Signature recorded')
                                ->success()
                                ->send();
                        }),
                    Action::make('unmark_signed')
                        ->label('Unmark')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->size(Size::Small)
                        ->requiresConfirmation()
                        ->modalDescription('The role itself stays assigned, but its permissions become unusable again immediately (checked live - see User::hasPermissionViaRole()).')
                        ->hidden(fn ($record) => $record?->signed_at === null)
                        ->action(function ($record) {
                            $record->update(['signed_at' => null]);

                            Notification::make()
                                ->title('Signature unmarked')
                                ->warning()
                                ->send();
                        }),
                ])->columnSpanFull(),
            ]);
    }
}
