<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Memberships\Schemas\MembershipForm;
use App\Models\MemberType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Membership form without the "Member" select - the owning User record
 * fills that in via the relationship, see MembershipForm::statusComponents().
 */
class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_type_id')
                    ->label('Member type')
                    ->relationship('memberType', 'name', fn ($query) => $query->orderBy('sort_order'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state) {
                            $set('has_voting_rights', MemberType::find($state)?->has_voting_rights ?? true);
                        }
                    })
                    ->required(),
                ...MembershipForm::statusComponents(userId: $this->getOwnerRecord()->getKey()),
                DatePicker::make('joined_at'),
                DatePicker::make('ended_at')
                    ->after('joined_at'),
                Toggle::make('has_voting_rights')
                    ->default(true),
                TextInput::make('board_role')
                    ->helperText('E.g. Chair, Secretary, Treasurer - leave blank for a regular member.'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('memberType.name')
                    ->label('Member type')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),
                TextColumn::make('joined_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('has_voting_rights')
                    ->label('Voting')
                    ->boolean(),
                TextColumn::make('board_role')
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
