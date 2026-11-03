<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Reservation;
use App\Models\Room;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * The reservations resource's landing page: an agenda instead of a table.
 * Week view puts the days side by side (optionally for one room), day view
 * the rooms. Blocks are laid out server-side - position from the time,
 * overlapping reservations in a column split it into lanes - so the view is
 * plain HTML/CSS. The table (ListReservations) is still there for search,
 * filters and bulk actions.
 */
class ReservationAgenda extends Page
{
    protected static string $resource = ReservationResource::class;

    protected string $view = 'filament.resources.reservations.pages.reservation-agenda';

    protected static ?string $title = 'Reservations';

    /**
     * The visible time range when nothing falls outside it - the same hours
     * residents can pick from in the booking form.
     */
    public const DAY_START_HOUR = 8;

    public const DAY_END_HOUR = 24;

    /**
     * Distinct room colours (hues), picked by room id.
     */
    protected const HUES = [212, 152, 32, 272, 342, 96, 188, 52, 4, 232];

    /** 'week' or 'day' - $view itself is the page's Blade template. */
    #[Url(as: 'view')]
    public string $mode = 'week';

    #[Url]
    public ?string $date = null;

    #[Url]
    public ?int $room = null;

    #[Url(as: 'cancelled')]
    public bool $showCancelled = false;

    public static function canAccess(array $parameters = []): bool
    {
        return ReservationResource::canViewAny();
    }

    public function mount(): void
    {
        $this->mode = in_array($this->mode, ['week', 'day'], true) ? $this->mode : 'week';
        $this->date = $this->day()->toDateString();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title;
    }

    public function getSubheading(): ?string
    {
        if ($this->mode === 'day') {
            return $this->day()->translatedFormat('l j F Y');
        }

        [$from, $until] = $this->range();
        $last = $until->copy()->subDay();

        return $from->month === $last->month
            ? $from->format('j').' – '.$last->translatedFormat('j F Y')
            : $from->translatedFormat('j M').' – '.$last->translatedFormat('j M Y');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label('Table')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('gray')
                ->url(ReservationResource::getUrl('list')),
            CreateAction::make(),
        ];
    }

    public function previous(): void
    {
        $this->date = $this->day()->sub($this->mode === 'day' ? '1 day' : '1 week')->toDateString();
    }

    public function next(): void
    {
        $this->date = $this->day()->add($this->mode === 'day' ? '1 day' : '1 week')->toDateString();
    }

    public function today(): void
    {
        $this->date = now()->toDateString();
    }

    public function showDay(string $date): void
    {
        $this->mode = 'day';
        $this->date = $date;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getRooms(): Collection
    {
        return Room::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Everything the view needs: the columns (days or rooms) with their
     * positioned blocks, and the hour range on the left.
     *
     * @return array{columns: array<int, array<string, mixed>>, start: int, end: int}
     */
    public function getAgenda(): array
    {
        [$from, $until] = $this->range();

        $reservations = Reservation::query()
            ->with(['room', 'user', 'organisation'])
            ->where('start_at', '<', $until)
            ->where('end_at', '>', $from)
            ->when(! $this->showCancelled, fn ($query) => $query->whereNotIn('status', [ReservationStatus::CANCELLED, ReservationStatus::REJECTED]))
            ->when($this->mode === 'week' && $this->room, fn ($query) => $query->where('room_id', $this->room))
            ->orderBy('start_at')
            ->get();

        $columns = $this->mode === 'day'
            ? $this->getRooms()->map(fn (Room $room) => [
                'key' => 'room-'.$room->id,
                'label' => $room->name,
                'sub' => null,
                'date' => $from,
                'today' => $from->isToday(),
                'reservations' => $reservations->where('room_id', $room->id),
            ])->values()->all()
            : collect(range(0, 6))->map(function (int $offset) use ($from, $reservations) {
                $day = $from->copy()->addDays($offset);

                return [
                    'key' => $day->toDateString(),
                    'label' => $day->translatedFormat('D'),
                    'sub' => $day->format('j'),
                    'date' => $day,
                    'today' => $day->isToday(),
                    'reservations' => $reservations->filter(fn (Reservation $r) => $r->start_at->lt($day->copy()->addDay()) && $r->end_at->gt($day)),
                ];
            })->all();

        // Widen the hour range if anything falls outside the usual one.
        $start = self::DAY_START_HOUR * 60;
        $end = self::DAY_END_HOUR * 60;

        foreach ($columns as $column) {
            foreach ($column['reservations'] as $reservation) {
                [$s, $e] = $this->minutesWithin($reservation, $column['date']);
                $start = min($start, intdiv($s, 60) * 60);
                $end = max($end, (int) ceil($e / 60) * 60);
            }
        }

        foreach ($columns as &$column) {
            $column['blocks'] = $this->layout($column['reservations'], $column['date'], $start, $end);
            $column['now'] = $column['today'] && ($minutes = now()->hour * 60 + now()->minute) >= $start && $minutes < $end
                ? ($minutes - $start) / ($end - $start) * 100
                : null;
            unset($column['reservations']);
        }

        return ['columns' => $columns, 'start' => $start, 'end' => $end];
    }

    /**
     * Positioned blocks for one column. Overlapping reservations form a
     * cluster; each gets the lowest free lane, and the whole cluster is
     * split into as many lanes as it needed.
     *
     * @param  Collection<int, Reservation>  $reservations
     * @return array<int, array<string, mixed>>
     */
    protected function layout(Collection $reservations, Carbon $day, int $start, int $end): array
    {
        $blocks = [];
        $cluster = [];
        $clusterEnd = -1;
        $laneEnds = [];

        $close = function () use (&$cluster, &$blocks, &$laneEnds) {
            foreach ($cluster as $index) {
                $blocks[$index]['lanes'] = count($laneEnds);
            }

            $cluster = [];
            $laneEnds = [];
        };

        foreach ($reservations->sortBy('start_at')->values() as $reservation) {
            [$s, $e] = $this->minutesWithin($reservation, $day);

            if ($s >= $clusterEnd) {
                $close();
            }

            $lane = collect($laneEnds)->search(fn (int $laneEnd) => $laneEnd <= $s);
            $lane = $lane === false ? count($laneEnds) : $lane;
            $laneEnds[$lane] = $e;
            $clusterEnd = max($clusterEnd, $e);

            $cluster[] = count($blocks);
            $blocks[] = [
                'reservation' => $reservation,
                'top' => ($s - $start) / ($end - $start) * 100,
                'height' => max(($e - $s) / ($end - $start) * 100, 1.5),
                'lane' => $lane,
                'lanes' => 1,
                'hue' => self::HUES[$reservation->room_id % count(self::HUES)],
                'time' => sprintf('%s – %s', $reservation->start_at->format('H:i'), $e >= 1440 ? '24:00' : $reservation->end_at->format('H:i')),
                'url' => ReservationResource::getUrl('view', ['record' => $reservation]),
                'fast' => isset($reservation->bookingSignals()['fast']),
            ];
        }

        $close();

        return $blocks;
    }

    /**
     * The reservation's start and end as minutes into $day, clipped to that
     * day - one running past midnight ends at 1440 here and continues in the
     * next day's column.
     *
     * @return array{int, int}
     */
    protected function minutesWithin(Reservation $reservation, Carbon $day): array
    {
        $dayStart = $day->copy()->startOfDay();
        $s = (int) max(0, $dayStart->diffInMinutes($reservation->start_at, false));
        $e = (int) min(1440, $dayStart->diffInMinutes($reservation->end_at, false));

        return [$s, max($e, $s + 15)];
    }

    /**
     * @return array{Carbon, Carbon} start inclusive, end exclusive
     */
    protected function range(): array
    {
        $day = $this->day();

        return $this->mode === 'day'
            ? [$day->copy()->startOfDay(), $day->copy()->startOfDay()->addDay()]
            : [$day->copy()->startOfWeek(), $day->copy()->startOfWeek()->addWeek()];
    }

    protected function day(): Carbon
    {
        try {
            return $this->date ? Carbon::parse($this->date)->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }
}
