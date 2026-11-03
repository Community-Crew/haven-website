<?php

namespace App\Filament\Vestide\Pages;

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Enums\CleanlinessOutcome;
use App\Filament\Vestide\Widgets\CleanlinessOverview;
use App\Models\ReservationCheck;
use App\Models\Room;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * The panel's home: every reviewed check-out, newest first and grouped by
 * day - which hangout, who had it booked, and how they left it (clean, left
 * dirty, or never checked out at all). Pending check-outs aren't on it until
 * a board member has reviewed them.
 *
 * The list doesn't name residents - opening an entry does (the same
 * slide-over as Reservations) - so it can't be skimmed as a log of who
 * booked what.
 */
class CleanlinessTimeline extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Tijdlijn';

    protected static ?string $title = 'Tijdlijn gedeelde ruimtes';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament.vestide.pages.cleanliness-timeline';

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function getSubheading(): ?string
    {
        return 'Hoe elke gedeelde ruimte na een reservering is achtergelaten, op basis van de uitcheckfoto\'s van bewoners en de beoordeling door het bestuur.';
    }

    protected function getHeaderWidgets(): array
    {
        return [CleanlinessOverview::class];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ReservationCheck::query()
                ->where('type', CheckType::CHECK_OUT)
                ->where('verification_status', '!=', CheckVerificationStatus::PENDING)
                ->with(['photos', 'reservation.room', 'reservation.organisation', 'reservation.user.unit', 'reservation.checks.photos']))
            ->defaultSort('occurred_at', 'desc')
            ->defaultGroup(Group::make('occurred_at')
                ->label('Dag')
                ->date()
                ->titlePrefixedWithLabel(false)
                ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('occurred_at', 'desc'))
                // HtmlString, not a plain string: for a date() group Filament
                // Carbon::parse()s a string title back, which fails on Dutch
                // day/month names ("zondag 27 september").
                ->getTitleFromRecordUsing(fn (ReservationCheck $record) => new HtmlString(e($record->occurred_at->translatedFormat('l j F Y')))))
            ->groupingSettingsHidden()
            ->columns([
                TextColumn::make('reservation.start_at')
                    ->label('Gereserveerd')
                    ->formatStateUsing(fn (ReservationCheck $record) => $record->reservation->start_at->format('H:i').' – '.$record->reservation->end_at->format('H:i')),
                TextColumn::make('reservation.room.name')
                    ->label('Ruimte')
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('outcome')
                    ->label('Achtergelaten')
                    ->badge()
                    ->state(fn (ReservationCheck $record) => CleanlinessOutcome::forCheck($record)),
                TextColumn::make('rejection_note')
                    ->label('Reden')
                    ->state(fn (ReservationCheck $record) => match (CleanlinessOutcome::forCheck($record)) {
                        CleanlinessOutcome::GHOSTED => 'Er zijn geen uitcheckfoto\'s ingestuurd.',
                        default => $record->rejection_note,
                    })
                    ->placeholder('-')
                    ->limit(80)
                    ->wrap(),
                ImageColumn::make('photo_urls')
                    ->label('Foto\'s')
                    ->square()
                    ->stacked()
                    ->limit(3),
            ])
            ->filters([
                SelectFilter::make('outcome')
                    ->label('Achtergelaten')
                    ->options(CleanlinessOutcome::class)
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? CleanlinessOutcome::from($data['value'])->scope($query)
                        : $query),
                SelectFilter::make('room')
                    ->label('Ruimte')
                    ->options(fn () => Room::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('reservation', fn (Builder $reservation) => $reservation->where('room_id', $data['value']))
                        : $query),
                Filter::make('period')
                    ->label('Periode')
                    ->schema([
                        DatePicker::make('from')->label('Vanaf'),
                        DatePicker::make('until')->label('Tot en met'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('occurred_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('occurred_at', '<=', $date))),
            ])
            ->recordAction('details')
            ->recordActions([
                Reservations::detailsAction(fn (ReservationCheck $record) => $record->reservation),
            ])
            ->emptyStateHeading('Nog geen beoordeelde uitchecks')
            ->emptyStateDescription('Uitchecks verschijnen hier zodra het bestuur ze heeft beoordeeld.');
    }
}
