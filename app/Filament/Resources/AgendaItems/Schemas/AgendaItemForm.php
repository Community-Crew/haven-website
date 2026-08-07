<?php

namespace App\Filament\Resources\AgendaItems\Schemas;

use App\Filament\Support\MediaCoverPicker;
use App\Filament\Support\MediaRichEditor;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgendaItemForm
{
    public const COVER_STATE_KEY = 'cover_image';

    public const COVER_COLLECTION = 'agenda-items-cover';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->columnSpanFull(),
                Select::make('agenda_id')
                    ->label('Agenda')
                    ->relationship('agenda', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Search or create a tag...')
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required(),
                        Textarea::make('description')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        Toggle::make('public')
                            ->default(true)
                            ->required(),
                    ])
                    ->required(),
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
                    ->schema(MediaCoverPicker::formComponents(self::COVER_STATE_KEY, self::COVER_COLLECTION)),
            ]);
    }
}
