<?php

namespace App\Filament\Resources\Units\Resources\RegistrationCodes\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager; // 👈 Import the core RelationManager class
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RegistrationCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Registration Code')
                    ->fontFamily('mono')
                    ->copyable(true)
                    ->copyMessage('Copied!')
                    ->searchable(),
                IconColumn::make('is_used')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                IconColumn::make('printed_at')
                    ->label('Label Printed')
                    ->boolean()
                    ->trueIcon('heroicon-o-printer')
                    ->falseIcon('heroicon-o-printer')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label('Generated At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('togglePrinted')
                    ->label(fn ($record) => $record->printed_at ? 'Mark Unprinted' : 'Mark Printed')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn ($record) => $record->togglePrinted()),
                DeleteAction::make()
                    ->label('Revoke')
                    ->authorize(fn () => auth()->user()->hasPermissionTo('DeleteAny:RegistrationCode')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('generateCode')
                    ->label('Generate Code')
                    ->icon('heroicon-o-key')
                    ->color('primary')
                    ->action(function (RelationManager $livewire) {
                        $livewire->getOwnerRecord()->registrationCodes()->create([]);
                    })
                    ->successNotificationTitle('Registration code generated!'),
            ]);
    }
}
