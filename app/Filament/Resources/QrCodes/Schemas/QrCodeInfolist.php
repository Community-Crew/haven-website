<?php

namespace App\Filament\Resources\QrCodes\Schemas;

use App\Models\QrCode;
use App\Services\QrImageBuilder;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QrCodeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('qr')
                            ->hiddenLabel()
                            ->state(fn (QrCode $record) => app(QrImageBuilder::class)->dataUri($record->short_url, size: 300))
                            ->columnSpan(1)
                            ->extraImgAttributes(['class' => 'rounded-lg border']),
                        TextEntry::make('name')
                            ->columnSpan(1),
                        TextEntry::make('short_url')
                            ->label('QR Code URL')
                            ->copyable()
                            ->columnSpan(1),
                        TextEntry::make('destination_url')
                            ->label('Currently redirects to')
                            ->copyable()
                            ->url(fn (QrCode $record) => $record->destination_url, shouldOpenInNewTab: true)
                            ->columnSpan(1),
                        TextEntry::make('visits')
                            ->label('Scans')
                            ->numeric(),
                        TextEntry::make('last_visited_at')
                            ->label('Last scanned')
                            ->dateTime()
                            ->placeholder('Never'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ]),
            ]);
    }
}
