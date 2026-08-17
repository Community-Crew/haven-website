<?php

namespace App\Filament\Resources\Memberships\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MembershipInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('Member'),
                TextEntry::make('memberType.name')
                    ->label('Member type')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),
                TextEntry::make('joined_at')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('ended_at')
                    ->date()
                    ->placeholder('-'),
                IconEntry::make('has_voting_rights')
                    ->boolean(),
                TextEntry::make('board_role')
                    ->placeholder('-'),
                TextEntry::make('statutes_accepted_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
