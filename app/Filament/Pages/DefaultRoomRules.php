<?php

namespace App\Filament\Pages;

use App\Filament\Support\RoomRulesRepeater;
use App\Models\DefaultRoomRules as DefaultRoomRulesModel;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Singleton editor for the rules that apply to every room.
 *
 * @property-read Schema $form
 */
class DefaultRoomRules extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;

    protected static string|null|UnitEnum $navigationGroup = 'Facilities';

    protected static ?string $navigationLabel = 'Default Room Rules';

    protected static ?string $title = 'Default Room Rules';

    protected static ?string $slug = 'default-room-rules';

    protected string $view = 'filament.pages.default-room-rules';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'rules' => DefaultRoomRulesModel::current()->ruleList(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rules for every room')
                    ->description('Shown above a room\'s own rules. Residents tick each rule when booking and again at check-in. Any change applies immediately.')
                    ->schema([RoomRulesRepeater::make()]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        DefaultRoomRulesModel::current()->update([
            'rules' => array_values($state['rules'] ?? []),
        ]);

        Notification::make()
            ->success()
            ->title('Default room rules saved')
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ])->key('form-actions'),
                    ]),
            ]);
    }
}
