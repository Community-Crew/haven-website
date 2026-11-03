<?php

namespace App\Filament\Vestide\Pages;

use App\Enums\CheckType;
use App\Enums\CleanlinessOutcome;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Url;

/**
 * All reservations of the shared rooms, which the privacy policy lets
 * Vestide inspect. The list never names the resident - only opening a
 * reservation does (see reservation-details) - so it can't be skimmed as a
 * log of who booked what. Opened from Bewoner opzoeken (see forResident())
 * it shows just that resident's reservations instead.
 */
class Reservations extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Reserveringen';

    protected static ?string $title = 'Reserveringen';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.vestide.pages.reservations';

    /**
     * The resident's id, encrypted: a plain ?bewoner=<id> could simply be
     * counted up to page through every resident, sidestepping the
     * search-by-name-only rule of Bewoner opzoeken. Only links that page
     * hands out decrypt.
     */
    #[Url(as: 'bewoner')]
    public ?string $resident = null;

    public static function forResident(User $user): string
    {
        return static::getUrl(['bewoner' => Crypt::encryptString((string) $user->getKey())]);
    }

    protected function residentId(): ?int
    {
        if (blank($this->resident)) {
            return null;
        }

        try {
            return (int) Crypt::decryptString($this->resident);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function statusLabel(ReservationStatus $status): string
    {
        return match ($status) {
            ReservationStatus::PENDING => 'Aangevraagd',
            ReservationStatus::APPROVED => 'Goedgekeurd',
            ReservationStatus::REJECTED => 'Afgewezen',
            ReservationStatus::CANCELLED => 'Geannuleerd',
        };
    }

    public function getResident(): ?User
    {
        return ($id = $this->residentId()) ? User::query()->whereNull('anonymized_at')->find($id) : null;
    }

    public function getTitle(): string
    {
        return ($resident = $this->getResident()) ? "Reserveringen van {$resident->name}" : static::$title;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('allReservations')
                ->label('Alle reserveringen')
                ->color('gray')
                ->icon(Heroicon::OutlinedXMark)
                ->visible(fn () => $this->resident !== null)
                ->action(function () {
                    $this->resident = null;
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Reservation::query()
                ->with(['room', 'organisation', 'user.unit', 'checks.photos'])
                // A link that doesn't decrypt shows nothing rather than everything.
                ->when($this->resident !== null, fn (Builder $query) => $query->where('user_id', $this->getResident()?->getKey() ?? 0)))
            ->defaultSort('start_at', 'desc')
            ->columns([
                TextColumn::make('start_at')
                    ->label('Datum')
                    ->formatStateUsing(fn (Reservation $record) => $record->start_at->translatedFormat('D j M Y'))
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Tijd')
                    ->formatStateUsing(fn (Reservation $record) => $record->start_at->format('H:i').' – '.$record->end_at->format('H:i')),
                TextColumn::make('room.name')
                    ->label('Ruimte')
                    ->weight('medium'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ReservationStatus $state) => self::statusLabel($state))
                    ->color(fn (ReservationStatus $state) => match ($state) {
                        ReservationStatus::APPROVED => 'success',
                        ReservationStatus::PENDING => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('outcome')
                    ->label('Achtergelaten')
                    ->badge()
                    ->state(fn (Reservation $record) => ($checkOut = self::checkOut($record)) ? CleanlinessOutcome::forCheck($checkOut) : null)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('room')
                    ->label('Ruimte')
                    ->options(fn () => Room::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn (Builder $query, $room) => $query->where('room_id', $room))),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(ReservationStatus::cases())->mapWithKeys(fn (ReservationStatus $status) => [$status->value => self::statusLabel($status)])->all()),
                SelectFilter::make('outcome')
                    ->label('Achtergelaten')
                    ->options(CleanlinessOutcome::class)
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereHas('checks', fn (Builder $checks) => CleanlinessOutcome::from($data['value'])->scope($checks))
                        : $query),
                Filter::make('period')
                    ->label('Periode')
                    ->schema([
                        DatePicker::make('from')->label('Vanaf'),
                        DatePicker::make('until')->label('Tot en met'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('start_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('start_at', '<=', $date))),
            ])
            ->recordAction('details')
            ->recordActions([
                self::detailsAction(fn (Reservation $record) => $record),
            ])
            ->emptyStateHeading('Geen reserveringen gevonden');
    }

    /**
     * The slide-over that names the resident - shared with the timeline,
     * whose rows are check-outs rather than reservations.
     *
     * @param  \Closure(mixed): Reservation  $reservation
     */
    public static function detailsAction(\Closure $reservation): Action
    {
        return Action::make('details')
            ->label('Bekijken')
            ->icon(Heroicon::OutlinedEye)
            ->slideOver()
            ->modalHeading(fn ($record) => $reservation($record)->room->name.' · '.$reservation($record)->start_at->translatedFormat('D j M Y'))
            ->modalContent(fn ($record) => view('filament.vestide.reservation-details', ['reservation' => $reservation($record)]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Sluiten');
    }

    protected static function checkOut(Reservation $reservation): ?ReservationCheck
    {
        return $reservation->checks->firstWhere('type', CheckType::CHECK_OUT);
    }
}
