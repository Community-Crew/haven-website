<?php

namespace App\Filament\Vestide\Pages;

use App\Services\AccountAlreadyExists;
use App\Services\VestideAccountService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Throwable;

/**
 * Vestide managers create and revoke their own colleagues' accounts here -
 * see VestideAccountService for how that maps onto Keycloak. Rows are
 * Keycloak users, not Haven users: someone who was just invited hasn't
 * logged in yet and has no Haven record.
 */
class StaffAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Medewerkers';

    protected static ?string $title = 'Medewerkers van Vestide';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.vestide.pages.staff-accounts';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isVestideManager();
    }

    public function getSubheading(): ?string
    {
        return 'Wie van Vestide toegang heeft tot dit dashboard. Nieuwe medewerkers krijgen een e-mail om een wachtwoord in te stellen.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Medewerker toevoegen')
                ->icon(Heroicon::OutlinedUserPlus)
                ->schema([
                    TextInput::make('first_name')->label('Voornaam')->required()->maxLength(100),
                    TextInput::make('last_name')->label('Achternaam')->required()->maxLength(100),
                    TextInput::make('email')->label('E-mailadres')->email()->required()->maxLength(255),
                    Toggle::make('manager')
                        ->label('Manager')
                        ->helperText('Managers kunnen zelf medewerkers toevoegen en verwijderen.'),
                ])
                ->modalSubmitActionLabel('Toevoegen en uitnodigen')
                ->action(function (array $data, VestideAccountService $accounts, Action $action) {
                    try {
                        $accounts->create(strtolower(trim($data['email'])), trim($data['first_name']), trim($data['last_name']), (bool) $data['manager']);
                    } catch (AccountAlreadyExists) {
                        Notification::make()
                            ->title('Er bestaat al een account met dit e-mailadres')
                            ->body('Vraag het bestuur van Haven om dat account toegang te geven.')
                            ->danger()
                            ->send();

                        $action->halt();
                    } catch (Throwable $e) {
                        report($e);
                        $this->failed();
                        $action->halt();
                    }

                    Notification::make()
                        ->title('Medewerker toegevoegd')
                        ->body("{$data['email']} krijgt een e-mail om een wachtwoord in te stellen.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => $this->accounts())
            ->columns([
                TextColumn::make('name')->label('Naam')->weight('medium'),
                TextColumn::make('email')->label('E-mailadres'),
                TextColumn::make('manager')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Manager' : 'Medewerker')
                    ->color(fn (bool $state) => $state ? 'primary' : 'gray'),
                TextColumn::make('invited')
                    ->label('Status')
                    ->badge()
                    ->state(fn (array $record) => match (true) {
                        ! $record['enabled'] => 'Uitgeschakeld',
                        $record['invited'] => 'Uitgenodigd',
                        default => 'Actief',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Actief' => 'success',
                        'Uitgenodigd' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('reinvite')
                    ->label('Opnieuw uitnodigen')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('gray')
                    ->visible(fn (array $record) => $record['invited'])
                    ->action(function (array $record, VestideAccountService $accounts) {
                        $this->attempt(fn () => $accounts->invite($record['id']), 'Uitnodiging opnieuw verstuurd');
                    }),
                Action::make('toggleManager')
                    ->label(fn (array $record) => $record['manager'] ? 'Maak medewerker' : 'Maak manager')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (array $record) => ! $this->isMe($record))
                    ->action(function (array $record, VestideAccountService $accounts) {
                        $this->attempt(fn () => $accounts->setManager($record['id'], ! $record['manager']), 'Rol aangepast');
                    }),
                Action::make('revoke')
                    ->label('Toegang intrekken')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (array $record) => "Toegang intrekken voor {$record['name']}")
                    ->modalDescription('Diegene wordt direct uitgelogd en kan dit dashboard niet meer openen. Het account zelf blijft bestaan.')
                    ->modalSubmitActionLabel('Intrekken')
                    // Not yourself - a manager shouldn't be able to lock the last manager out by accident.
                    ->visible(fn (array $record) => ! $this->isMe($record))
                    ->action(function (array $record, VestideAccountService $accounts) {
                        $this->attempt(fn () => $accounts->revoke($record['id']), 'Toegang ingetrokken');
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Nog geen medewerkers');
    }

    /**
     * Keycloak unreachable shouldn't take the page down with it.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function accounts(): array
    {
        try {
            return app(VestideAccountService::class)->accounts()->keyBy('id')->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $record
     */
    protected function isMe(array $record): bool
    {
        return $record['id'] === auth()->user()?->keycloak_id;
    }

    protected function attempt(callable $callback, string $success): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
            $this->failed();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }

    protected function failed(): void
    {
        Notification::make()
            ->title('Dat is niet gelukt')
            ->body('Er ging iets mis bij het accountbeheer. Probeer het later opnieuw of neem contact op met het bestuur van Haven.')
            ->danger()
            ->send();
    }
}
