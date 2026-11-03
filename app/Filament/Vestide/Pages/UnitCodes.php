<?php

namespace App\Filament\Vestide\Pages;

use App\Models\Unit;
use App\Models\User;
use App\Services\ResidentOffboardingService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Issue a registration code for a single unit outside the monthly mutaties
 * import - e.g. a mid-month move. Goes through the same handover as the
 * import (ResidentOffboardingService::handOverUnit()), so whoever lives there
 * now is moved out and gets the usual move-out notice.
 *
 * Shows how many people live in each unit, not who: Vestide may look a
 * resident up by name (which finds their unit), but this mustn't double as
 * a browsable list of residents. The names only appear in the confirmation
 * for the one unit being handed over.
 */
class UnitCodes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Units & codes';

    protected static ?string $title = 'Units & registratiecodes';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.vestide.pages.unit-codes';

    /**
     * Same format as Unit's name accessor ("Pollux 0709D", "Terra 62 C-E"),
     * so what's on screen is what can be searched for.
     */
    protected const NAME_SQL = "CASE WHEN building = 'Terra'
        THEN 'Terra ' || floor::text || ' ' || unit || '-' || COALESCE(subunit, '')
        ELSE building || ' ' || LPAD(floor::text, 2, '0') || LPAD(unit, 2, '0') || COALESCE(subunit, '') END";

    public function table(Table $table): Table
    {
        return $table
            ->query(Unit::query()->with([
                'users',
                'registrationCodes' => fn ($query) => $query->where('is_used', false)->latest(),
            ]))
            ->defaultSort(fn (Builder $query) => $query->orderBy('building')->orderBy('floor')->orderBy('unit')->orderBy('subunit'))
            ->searchable()
            ->searchPlaceholder('Unit of naam van een bewoner')
            ->searchUsing(fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->whereRaw('('.self::NAME_SQL.') ILIKE ?', ['%'.$search.'%'])
                ->orWhereHas('users', fn (Builder $users) => $users->where('name', 'ILIKE', '%'.$search.'%'))))
            ->columns([
                TextColumn::make('name')
                    ->label('Unit')
                    ->weight('medium'),
                TextColumn::make('residents')
                    ->label('Bewoners')
                    ->state(fn (Unit $record) => $record->users->count() ?: null)
                    ->formatStateUsing(fn (int $state) => $state === 1 ? '1 bewoner' : "{$state} bewoners")
                    ->placeholder('Leeg'),
                TextColumn::make('open_code')
                    ->label('Ongebruikte code')
                    ->state(fn (Unit $record) => $record->registrationCodes->first()?->code)
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Gekopieerd!')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('building')
                    ->label('Gebouw')
                    ->options(fn () => Unit::query()->distinct()->orderBy('building')->pluck('building', 'building')->all()),
            ])
            ->recordActions([
                Action::make('issueCode')
                    ->label('Nieuwe code')
                    ->icon(Heroicon::OutlinedKey)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Unit $record) => "Nieuwe registratiecode voor {$record->name}")
                    ->modalDescription(fn (Unit $record) => $record->users->isEmpty()
                        ? 'Er wordt een nieuwe code aangemaakt voor de volgende huurder.'
                        : $record->users->pluck('name')->implode(' en ')
                            .($record->users->count() === 1
                                ? ' wordt uitgeschreven: verliest de toegang tot Haven en krijgt de gebruikelijke verhuismail.'
                                : ' worden uitgeschreven: ze verliezen de toegang tot Haven en krijgen de gebruikelijke verhuismail.')
                            .' Daarna wordt er een nieuwe code aangemaakt voor de volgende huurder.')
                    ->modalSubmitActionLabel('Code aanmaken')
                    ->color(fn (Unit $record) => $record->users->isEmpty() ? 'primary' : 'danger')
                    ->action(function (Unit $record, ResidentOffboardingService $offboarding) {
                        ['code' => $code, 'moved_out' => $movedOut] = $offboarding->handOverUnit($record);

                        Notification::make()
                            ->title("Nieuwe code voor {$record->name}: {$code->code}")
                            ->body($movedOut === []
                                ? null
                                : 'Uitgeschreven: '.collect($movedOut)->map(fn (User $user) => $user->name)->implode(', ').'.')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ]);
    }
}
