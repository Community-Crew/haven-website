<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Support\PostalAddress;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')
                            ->label('Email address'),
                        TextEntry::make('phone')
                            ->label('Phone number')
                            ->placeholder('-'),
                        TextEntry::make('birth_date')
                            ->date('d-m-Y')
                            ->placeholder('-'),
                        TextEntry::make('unit')
                            ->label('Unit')
                            ->placeholder('No unit assigned')
                            ->formatStateUsing(fn ($state) => $state?->name),
                        TextEntry::make('postal_address')
                            ->label('Postal address')
                            ->state(fn ($record) => PostalAddress::format($record?->postal_address))
                            ->visible(fn ($record) => $record?->moved_out_at !== null)
                            ->placeholder('-'),
                        TextEntry::make('access_tag')
                            ->label('Tag / key')
                            ->state(fn ($record) => $record?->access_tag_hash ? 'Linked' : 'Not linked')
                            ->badge()
                            ->color(fn ($record) => $record?->access_tag_hash ? 'success' : 'gray'),
                    ]),
                Section::make('Membership')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('membership_status')
                            ->label('Status')
                            ->state(fn ($record) => $record?->currentMembership?->status->getLabel() ?? 'No membership')
                            ->badge()
                            ->color(fn ($record) => $record?->currentMembership?->status->getColor() ?? 'gray'),
                        TextEntry::make('currentMembership.memberType.name')
                            ->label('Member type')
                            ->placeholder('-'),
                        TextEntry::make('currentMembership.documents_accepted_at')
                            ->label('Accepted statutes & house rules')
                            ->dateTime()
                            ->placeholder('Not yet'),
                        TextEntry::make('currentMembership.identity_verified_at')
                            ->label('ID checked at')
                            ->dateTime()
                            ->placeholder('Not checked yet'),
                        TextEntry::make('currentMembership.identityVerifiedBy.name')
                            ->label('ID checked by')
                            ->placeholder('-'),
                    ]),
                Section::make('Account')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('keycloak_id'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('activation_status')
                            ->label('Account Status')
                            ->state(fn ($record) => $record?->activated_at ? 'Activated' : 'Not Activated yet')
                            ->badge()
                            ->color(fn ($record) => $record?->activated_at ? 'success' : 'danger')
                            ->hintAction(
                                fn ($record) => $record?->activated_at === null
                                    ? Action::make('activate')
                                        ->label('Activate Now')
                                        ->icon('heroicon-m-check-circle')
                                        ->color('success')
                                        ->action(function ($record) {
                                            $record->update(['activated_at' => now()]);
                                        })
                                    : Action::make('deactivate')
                                        ->label('Deactivate')
                                        ->icon('heroicon-m-x-circle')
                                        ->color('danger')
                                        ->requiresConfirmation()
                                        ->action(function ($record) {
                                            $record->update(['activated_at' => null]);
                                        })
                            ),
                    ]),
            ]);
    }
}
