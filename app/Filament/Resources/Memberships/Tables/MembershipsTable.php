<?php

namespace App\Filament\Resources\Memberships\Tables;

use App\Enums\MembershipStatus;
use App\Filament\Support\MembershipReviewActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MembershipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable(),
                TextColumn::make('memberType.name')
                    ->label('Member type')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),
                IconColumn::make('id_checked')
                    ->label('ID checked')
                    ->state(fn ($record) => $record->identity_verified_at !== null)
                    ->boolean(),
                TextColumn::make('joined_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('has_voting_rights')
                    ->label('Voting')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(MembershipStatus::cases())
                        ->mapWithKeys(fn (MembershipStatus $status) => [$status->value => $status->getLabel()])
                        ->all()),
                SelectFilter::make('member_type_id')
                    ->label('Member type')
                    ->relationship('memberType', 'name'),
            ])
            ->recordActions([
                MembershipReviewActions::approve(),
                MembershipReviewActions::reject(),
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
