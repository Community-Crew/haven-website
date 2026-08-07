<?php

namespace App\Filament\Resources\Agendas\RelationManagers;

use App\Filament\Resources\AgendaItems\Schemas\AgendaItemForm;
use App\Filament\Support\MediaCoverPicker;
use App\Filament\Support\MediaRichEditor;
use App\Models\AgendaItem;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->columnSpanFull(),
                TextInput::make('short_description')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                MediaRichEditor::make('description', 'agenda-items')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('start_date')
                    ->required(),
                DateTimePicker::make('end_date')
                    ->required()
                    ->after('start_date'),
                Select::make('user_id')
                    ->label('Organiser')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})")
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('organisation_id')
                    ->label('Organisation')
                    ->relationship('organisation', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Section::make('Item Image')
                    ->description('Manage the public cover image for this agenda item.')
                    ->schema(MediaCoverPicker::formComponents(AgendaItemForm::COVER_STATE_KEY, AgendaItemForm::COVER_COLLECTION)),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Organiser')
                    ->searchable(),
                TextColumn::make('organisation.name')
                    ->label('Organisation')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('start_date')
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(fn (AgendaItem $record, array $data) => MediaCoverPicker::sync(
                        $record,
                        AgendaItemForm::COVER_STATE_KEY,
                        AgendaItemForm::COVER_COLLECTION,
                        $data,
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    // EditAction::make() already sets its own ->fillForm() default (the
                    // record's attributesToArray()) in its setUp(); calling ->fillForm()
                    // again *replaces* that closure rather than adding to it, so this
                    // has to reproduce the default and merge the cover data into it,
                    // not just return the cover data alone.
                    ->fillForm(fn (AgendaItem $record): array => [
                        ...$record->attributesToArray(),
                        ...MediaCoverPicker::hydrate(
                            $record,
                            AgendaItemForm::COVER_STATE_KEY,
                            AgendaItemForm::COVER_COLLECTION,
                        ),
                    ])
                    ->after(fn (AgendaItem $record, array $data) => MediaCoverPicker::sync(
                        $record,
                        AgendaItemForm::COVER_STATE_KEY,
                        AgendaItemForm::COVER_COLLECTION,
                        $data,
                    )),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
