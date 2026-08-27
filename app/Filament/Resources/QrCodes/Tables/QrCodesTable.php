<?php

namespace App\Filament\Resources\QrCodes\Tables;

use App\Models\QrCode;
use App\Services\QrImageBuilder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QrCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('short_url')
                    ->label('QR Code URL')
                    ->copyable()
                    ->copyMessage('Copied!'),
                TextColumn::make('destination_url')
                    ->label('Redirects to')
                    ->limit(40)
                    ->tooltip(fn (QrCode $record) => $record->destination_url)
                    ->searchable(),
                TextColumn::make('visits')
                    ->label('Scans')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_visited_at')
                    ->label('Last scanned')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('downloadQr')
                    ->label('Download QR')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (QrCode $record, QrImageBuilder $qrImageBuilder) => response()->streamDownload(
                        fn () => print ($qrImageBuilder->png($record->short_url, size: 600)),
                        "qr-{$record->code}.png",
                        ['Content-Type' => 'image/png'],
                    )),
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
