<?php

namespace App\Filament\Pages;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Day-of-week x hour-of-day heatmap of approved reservations over the last
 * few months: a combined view across all rooms, plus a small-multiples grid
 * of every individual room.
 *
 * The hour range shown is derived from the combined data (padded by an hour
 * on each side) rather than hardcoded, since opening hours vary per room/role
 * via ReservationPolicy and aren't a single fixed window.
 */
class ReservationHeatmap extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static ?string $navigationLabel = 'Reservation Heatmap';

    protected static ?string $title = 'Reservation Heatmap';

    protected static ?string $slug = 'reservation-heatmap';

    protected string $view = 'filament.pages.reservation-heatmap';

    protected int $months = 3;

    public function getRooms(): Collection
    {
        return Room::query()->orderBy('name')->get();
    }

    public function getCombinedHeatmap(): array
    {
        return $this->buildHeatmap(null);
    }

    /** @return Collection<int, array> one heatmap per room, keyed by room id */
    public function getPerRoomHeatmaps(): Collection
    {
        return $this->getRooms()->map(fn (Room $room) => [
            'room' => $room,
            ...$this->buildHeatmap($room->id),
        ]);
    }

    /**
     * Hour range (inclusive) to render as columns, padded by one hour on
     * each side of whatever hours actually have reservations combined
     * across all rooms - kept independent of the selected room so switching
     * rooms doesn't jump the grid around.
     *
     * @return array{0: int, 1: int}
     */
    public function getHourRange(): array
    {
        $usedHours = collect($this->getCombinedHeatmap()['grid'])
            ->flatMap(fn (array $hours) => array_keys(array_filter($hours)));

        if ($usedHours->isEmpty()) {
            return [8, 20];
        }

        return [
            max(0, $usedHours->min() - 1),
            min(23, $usedHours->max() + 1),
        ];
    }

    /**
     * @return array{grid: array<int, array<int, int>>, colors: array<int, array<int, string>>, max: int, total: int}
     */
    protected function buildHeatmap(?int $roomId): array
    {
        $since = now()->subMonths($this->months)->startOfDay();

        $startTimes = Reservation::query()
            ->where('status', ReservationStatus::APPROVED)
            ->where('start_at', '>=', $since)
            ->when($roomId, fn ($query) => $query->where('room_id', $roomId))
            ->pluck('start_at');

        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        $max = 0;

        foreach ($startTimes as $startAt) {
            $day = $startAt->dayOfWeekIso - 1; // 0 = Monday .. 6 = Sunday
            $hour = $startAt->hour;

            $grid[$day][$hour]++;
            $max = max($max, $grid[$day][$hour]);
        }

        return [
            'grid' => $grid,
            'colors' => $this->colorGrid($grid, $max),
            'max' => $max,
            'total' => $startTimes->count(),
        ];
    }

    /**
     * Color for the legend swatch at a given fraction (0-1) of "busyness",
     * using the same scale as the grid cells - called directly from the
     * top-level Blade view, which (unlike the nested @include'd grid
     * partial) does inherit Livewire's `$this` binding to the component.
     */
    public function legendColor(float $fraction): string
    {
        return $this->intensityColor($fraction);
    }

    /**
     * Background color per cell: transparent when empty, otherwise the
     * panel's primary blue scaled by how busy the cell is relative to the
     * busiest cell in the same heatmap. Precomputed here (rather than called
     * from the Blade partial) since nested @include views don't reliably
     * inherit Livewire's `$this` binding to the component.
     *
     * @param  array<int, array<int, int>>  $grid
     * @return array<int, array<int, string>>
     */
    protected function colorGrid(array $grid, int $max): array
    {
        return array_map(
            fn (array $hours) => array_map(
                fn (int $count) => $this->intensityColor($max === 0 ? 0 : $count / $max),
                $hours,
            ),
            $grid,
        );
    }

    protected function intensityColor(float $fraction): string
    {
        if ($fraction <= 0) {
            return 'transparent';
        }

        return sprintf('rgba(37, 99, 235, %.2f)', 0.12 + (0.8 * $fraction));
    }
}
