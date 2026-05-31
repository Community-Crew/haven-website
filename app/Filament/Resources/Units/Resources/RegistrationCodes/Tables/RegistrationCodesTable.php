<?php

namespace App\Filament\Resources\Units\Resources\RegistrationCodes\Tables;

use App\Models\RegistrationCode;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
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
                    ->trueColor('success') // Green for used
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
                DeleteAction::make()
                    ->label('Revoke')
                    ->authorize(fn() => auth()->user()->hasPermissionTo('DeleteAny:RegistrationCode')),
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
                    ->action(function () {
                        static::getOwnerRecord()->registrationCodes()->create([]);
                    })
                    ->successNotificationTitle('Registration code generated!'),
            ]);
    }
}
