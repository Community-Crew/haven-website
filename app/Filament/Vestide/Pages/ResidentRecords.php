<?php

namespace App\Filament\Vestide\Pages;

use App\Enums\CleanlinessOutcome;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Look up one resident's track record - how often they left a hangout clean,
 * dirty, or didn't check out - as the starting point for any follow-up,
 * which is up to Vestide. Clicking one opens their reservations.
 *
 * Lookup only, by name: the table stays empty until at least MIN_SEARCH
 * characters are typed, so this can't be paged through as a list of every
 * resident. The privacy policy grants Vestide insight into reservations,
 * not a resident directory.
 */
class ResidentRecords extends Page implements HasTable
{
    use InteractsWithTable;

    public const MIN_SEARCH = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Bewoner opzoeken';

    protected static ?string $title = 'Bewoner opzoeken';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.vestide.pages.resident-records';

    public function getSubheading(): ?string
    {
        return 'Zoek een bewoner op naam om te zien hoe vaak diegene een gedeelde ruimte schoon of vies heeft achtergelaten. Klik op een bewoner voor diens reserveringen.';
    }

    public function table(Table $table): Table
    {
        $counts = collect(CleanlinessOutcome::cases())
            ->mapWithKeys(fn (CleanlinessOutcome $outcome) => [
                "reservationChecks as {$outcome->value}_count" => fn (Builder $query) => $outcome->scope($query),
            ])
            ->all();

        return $table
            ->query(fn () => User::query()
                ->with('unit')
                ->withCount($counts)
                ->whereNull('anonymized_at')
                ->when(mb_strlen(trim((string) $this->getTableSearch())) < self::MIN_SEARCH, fn (Builder $query) => $query->whereRaw('1 = 0')))
            ->searchable()
            ->searchPlaceholder('Naam van de bewoner')
            ->searchUsing(fn (Builder $query, string $search) => $query->where('name', 'ILIKE', '%'.$search.'%'))
            ->defaultSort('name')
            ->paginated([10])
            ->columns([
                TextColumn::make('name')
                    ->label('Bewoner')
                    ->weight('medium'),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->placeholder('Verhuisd'),
                TextColumn::make('clean_count')
                    ->label(CleanlinessOutcome::CLEAN->getLabel())
                    ->numeric()
                    ->color('success'),
                TextColumn::make('dirty_count')
                    ->label(CleanlinessOutcome::DIRTY->getLabel())
                    ->numeric()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn (int $state) => $state > 0 ? 'bold' : null),
                TextColumn::make('ghosted_count')
                    ->label(CleanlinessOutcome::GHOSTED->getLabel())
                    ->numeric()
                    ->color(fn (int $state) => $state > 0 ? 'warning' : 'gray'),
            ])
            ->recordUrl(fn (User $record) => Reservations::forResident($record))
            ->emptyStateIcon(Heroicon::OutlinedMagnifyingGlass)
            ->emptyStateHeading(fn () => mb_strlen(trim((string) $this->getTableSearch())) < self::MIN_SEARCH
                ? 'Zoek een bewoner op naam'
                : 'Geen bewoner gevonden')
            ->emptyStateDescription(fn () => mb_strlen(trim((string) $this->getTableSearch())) < self::MIN_SEARCH
                ? 'Typ minstens '.self::MIN_SEARCH.' letters van de naam.'
                : 'Controleer de spelling, of zoek op een deel van de naam.');
    }
}
