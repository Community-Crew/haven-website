<?php

namespace App\Filament\Vestide\Pages;

use App\Enums\CheckType;
use App\Enums\CleanlinessOutcome;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationCheckService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * One card per shared room with its current state, taken from how it was
 * left after its most recent finished reservation: left clean and nobody has
 * had it since, so it's still clean. Only reservations are seen - if a room
 * is also used without one, that isn't reflected here.
 *
 * Vestide can mark a room as being cleaned (RoomStatus::CLEANING, which
 * residents also see on the room) and as cleaned (cleaned_at). A cleaning
 * newer than the last reservation overrides how that reservation left it.
 *
 * No names anywhere on this page, including the history slide-over.
 */
class RoomStatuses extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static ?string $navigationLabel = 'Ruimtes';

    protected static ?string $title = 'Status van de ruimtes';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.vestide.pages.room-statuses';

    /**
     * How many past reservations the dots and the slide-over go back.
     */
    public const HISTORY = 10;

    public function getSubheading(): ?string
    {
        return 'Hoe elke ruimte er nu bij ligt, op basis van hoe die na de laatste reservering is achtergelaten.';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getRooms(): Collection
    {
        return Room::query()->orderBy('name')->get()->map(fn (Room $room) => [
            'room' => $room,
            'history' => $history = $this->history($room),
            'in_use' => Reservation::query()
                ->where('room_id', $room->id)
                ->where('status', ReservationStatus::APPROVED)
                ->where('start_at', '<=', now())
                ->where('end_at', '>', now())
                ->exists(),
            ...$this->state($room, $history),
        ]);
    }

    /**
     * The room's last finished reservations, newest first, each with how it
     * was left (null while the check-out is missing or pending).
     *
     * @return Collection<int, array{reservation: Reservation, outcome: ?CleanlinessOutcome, pending: bool}>
     */
    protected function history(Room $room): Collection
    {
        return Reservation::query()
            ->where('room_id', $room->id)
            ->where('status', ReservationStatus::APPROVED)
            ->where('end_at', '<=', now())
            ->with('checks')
            ->latest('end_at')
            ->limit(self::HISTORY)
            ->get()
            ->map(function (Reservation $reservation) {
                $checkOut = $reservation->checks->firstWhere('type', CheckType::CHECK_OUT);

                return [
                    'reservation' => $reservation,
                    'check_out' => $checkOut,
                    'outcome' => $checkOut ? CleanlinessOutcome::forCheck($checkOut) : null,
                    'pending' => $checkOut === null || CleanlinessOutcome::forCheck($checkOut) === null,
                ];
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $history
     * @return array{label: string, color: string, icon: string, detail: ?string}
     */
    protected function state(Room $room, Collection $history): array
    {
        $last = $history->first();

        if ($room->status === RoomStatus::CLEANING) {
            return ['label' => 'Wordt schoongemaakt', 'color' => 'info', 'icon' => 'heroicon-m-sparkles', 'detail' => 'Zet de ruimte op "Schoongemaakt" zodra de schoonmaak klaar is.'];
        }

        if ($room->cleaned_at && (! $last || $room->cleaned_at->gt($last['reservation']->end_at))) {
            return ['label' => 'Schoon', 'color' => 'success', 'icon' => 'heroicon-m-sparkles', 'detail' => 'Schoongemaakt door Vestide op '.$room->cleaned_at->translatedFormat('D j M').', '.$room->cleaned_at->format('H:i').'.'];
        }

        if (! $last) {
            return ['label' => 'Nog niet gebruikt', 'color' => 'gray', 'icon' => 'heroicon-m-minus-circle', 'detail' => 'Er is nog geen reservering afgelopen.'];
        }

        $ended = $last['reservation']->end_at;
        $since = 'Sinds de reservering van '.$ended->translatedFormat('D j M').', '.$ended->format('H:i').'.';

        if ($last['pending']) {
            $awaiting = $last['check_out'] === null && $ended->gt(now()->subHours(ReservationCheckService::CHECK_OUT_GRACE_HOURS))
                ? 'De laatste reservering is net afgelopen; de uitcheck komt nog.'
                : 'De uitcheckfoto\'s van de laatste reservering worden nog beoordeeld.';
            $previous = $history->skip(1)->first(fn (array $entry) => $entry['outcome'] !== null);

            return [
                'label' => 'Wacht op beoordeling',
                'color' => 'info',
                'icon' => 'heroicon-m-clock',
                'detail' => $awaiting.($previous ? ' Daarvoor: '.mb_strtolower($previous['outcome']->getLabel()).'.' : ''),
            ];
        }

        return match ($last['outcome']) {
            CleanlinessOutcome::CLEAN => ['label' => 'Schoon', 'color' => 'success', 'icon' => 'heroicon-m-sparkles', 'detail' => $since],
            CleanlinessOutcome::DIRTY => ['label' => 'Vies achtergelaten', 'color' => 'danger', 'icon' => 'heroicon-m-exclamation-triangle', 'detail' => $since],
            CleanlinessOutcome::GHOSTED => ['label' => 'Onbekend', 'color' => 'warning', 'icon' => 'heroicon-m-question-mark-circle', 'detail' => 'Na de reservering van '.$ended->translatedFormat('D j M').' is niet uitgecheckt.'],
        };
    }

    public static function roomStatusNote(RoomStatus $status): ?string
    {
        return match ($status) {
            RoomStatus::MAINTENANCE => 'Buiten gebruik',
            // CLEANING is the card's main state rather than a side note.
            default => null,
        };
    }

    public function startCleaningAction(): Action
    {
        return Action::make('startCleaning')
            ->label('Schoonmaak starten')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'Schoonmaak starten: '.Room::find($arguments['room'])?->name)
            ->modalDescription('Bewoners zien in de app dat de ruimte wordt schoongemaakt. Reserveren blijft mogelijk.')
            ->modalSubmitActionLabel('Starten')
            ->action(function (array $arguments) {
                $room = Room::findOrFail($arguments['room']);
                $room->update(['status' => RoomStatus::CLEANING]);

                Notification::make()->title("{$room->name} wordt schoongemaakt")->success()->send();
            });
    }

    public function finishCleaningAction(): Action
    {
        return Action::make('finishCleaning')
            ->label('Schoongemaakt')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'Schoongemaakt: '.Room::find($arguments['room'])?->name)
            ->modalDescription('De ruimte staat daarna op "Schoon" tot de volgende reservering is afgelopen.')
            ->modalSubmitActionLabel('Schoongemaakt')
            ->action(function (array $arguments) {
                $room = Room::findOrFail($arguments['room']);
                $room->update([
                    // Out of order stays out of order; anything else is free again.
                    'status' => $room->status === RoomStatus::MAINTENANCE ? RoomStatus::MAINTENANCE : RoomStatus::AVAILABLE,
                    'cleaned_at' => now(),
                    'cleaned_by' => auth()->id(),
                ]);

                Notification::make()->title("{$room->name} is schoon")->success()->send();
            });
    }

    public function historyAction(): Action
    {
        return Action::make('history')
            ->slideOver()
            ->modalHeading(fn (array $arguments) => Room::find($arguments['room'])?->name)
            ->modalDescription('De laatste '.self::HISTORY.' afgelopen reserveringen.')
            ->modalContent(fn (array $arguments) => view('filament.vestide.room-history', [
                'room' => $room = Room::find($arguments['room']),
                'history' => $room ? $this->history($room) : collect(),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Sluiten');
    }
}
