<?php

namespace App\Filament\Resources\FaqCategories\Schemas;

use App\Models\FaqCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqCategoryForm
{
    /**
     * How many decisions deep a flowchart can go - a decision inside a
     * decision is already cramped on a phone.
     */
    protected const MAX_DECISION_DEPTH = 2;

    /**
     * An app path (/dashboard/rooms), a web address or an email link.
     */
    public const LINK_PATTERN = '/^(\/[^\/]|https?:\/\/|mailto:)/';

    protected const LINK_HELP = 'A page in the app (e.g. /dashboard/rooms, /dashboard/reservations, /dashboard/profile, /privacy-policy) or a full web address.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextInput::make('title')
                            ->label('Title (Dutch)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('title_en')
                            ->label('Title (English)')
                            ->helperText('Leave empty to show the Dutch title in English too.')
                            ->maxLength(255),
                        TextInput::make('icon')
                            ->helperText('A single emoji, e.g. 📷')
                            ->maxLength(16),
                    ]),

                Section::make('Flowchart')
                    ->description('Optional - shown above the questions. Steps run top to bottom; give a step two options to make it a decision that splits the chart.')
                    ->columnSpanFull()
                    ->collapsible()
                    ->components([
                        self::stepsRepeater('flowchart', 0)
                            ->hiddenLabel()
                            ->addActionLabel('Add step'),
                    ]),

                Section::make('Questions')
                    ->columnSpanFull()
                    ->components([
                        Repeater::make('questions')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->reorderableWithDragAndDrop()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                            ->addActionLabel('Add question')
                            ->columns(2)
                            ->components([
                                TextInput::make('question')
                                    ->label('Question (Dutch)')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('question_en')
                                    ->label('Question (English)')
                                    ->maxLength(255),
                                self::answerEditor('answer')
                                    ->label('Answer (Dutch)')
                                    ->required(),
                                self::answerEditor('answer_en')
                                    ->label('Answer (English)')
                                    ->helperText('Leave both English fields empty to show the Dutch text in English too.'),
                                Toggle::make('is_published')
                                    ->label('Published')
                                    ->helperText('Unpublished questions are hidden from the app.')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    protected static function answerEditor(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons([['bold', 'italic', 'link'], ['bulletList', 'orderedList'], ['undo', 'redo']])
            // Off already without the attachFiles button, but explicit so a
            // Filament upgrade can't turn uploads (pasted/dropped files) on.
            ->fileAttachments(false)
            ->hint('Links: '.self::LINK_HELP);
    }

    /**
     * The steps of a flowchart, or of one option of a decision. Recursive:
     * each step can hold options with their own steps, down to
     * MAX_DECISION_DEPTH.
     */
    protected static function stepsRepeater(string $name, int $depth): Repeater
    {
        $fields = [
            TextInput::make('icon')
                ->helperText('A single emoji.')
                ->maxLength(16),
            Select::make('tone')
                ->label('Colour')
                ->options(FaqCategory::STEP_TONES)
                ->default('default')
                ->selectablePlaceholder(false),
            TextInput::make('title')
                ->label('Title (Dutch)')
                ->required()
                ->maxLength(120),
            TextInput::make('title_en')
                ->label('Title (English)')
                ->maxLength(120),
            TextInput::make('detail')
                ->label('Detail (Dutch)')
                ->maxLength(255),
            TextInput::make('detail_en')
                ->label('Detail (English)')
                ->maxLength(255),
            TextInput::make('url')
                ->label('Link')
                ->helperText('Optional - tapping the step opens it. '.self::LINK_HELP)
                ->regex(self::LINK_PATTERN)
                ->maxLength(255)
                ->columnSpanFull(),
        ];

        if ($depth < self::MAX_DECISION_DEPTH) {
            $fields[] = Repeater::make('branches')
                ->label('Decision options')
                ->helperText('Leave empty for a normal step. Add two options to make this a decision; each option gets its own steps.')
                ->maxItems(2)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                ->addActionLabel('Add option')
                ->columns(2)
                ->columnSpanFull()
                ->default([])
                ->components([
                    TextInput::make('label')
                        ->label('Label (Dutch)')
                        ->required()
                        ->maxLength(40),
                    TextInput::make('label_en')
                        ->label('Label (English)')
                        ->maxLength(40),
                    self::stepsRepeater('steps', $depth + 1)
                        ->label('Steps for this option')
                        ->addActionLabel('Add step')
                        ->minItems(1)
                        ->columnSpanFull(),
                ]);
        }

        return Repeater::make($name)
            ->reorderableWithDragAndDrop()
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->itemLabel(fn (array $state): ?string => trim(($state['icon'] ?? '').' '.($state['title'] ?? '')) ?: null)
            ->columns(2)
            ->components($fields);
    }
}
