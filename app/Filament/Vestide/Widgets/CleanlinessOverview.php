<?php

namespace App\Filament\Vestide\Widgets;

use App\Enums\CleanlinessOutcome;
use App\Models\ReservationCheck;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * The last 30 days of reviewed check-outs at a glance, each compared with
 * the 30 days before.
 */
class CleanlinessOverview extends StatsOverviewWidget
{
    protected const DAYS = 30;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

    protected ?string $heading = 'Afgelopen 30 dagen';

    protected function getStats(): array
    {
        $now = now();
        $current = $this->counts($now->copy()->subDays(self::DAYS), $now);
        $previous = $this->counts($now->copy()->subDays(self::DAYS * 2), $now->copy()->subDays(self::DAYS));

        $rate = fn (array $counts) => array_sum($counts) > 0
            ? (int) round($counts[CleanlinessOutcome::CLEAN->value] / array_sum($counts) * 100)
            : null;

        $stats = [];

        foreach (CleanlinessOutcome::cases() as $outcome) {
            $stats[] = Stat::make($outcome->getLabel(), $current[$outcome->value])
                ->description($previous[$outcome->value].' in de 30 dagen daarvoor')
                ->icon($outcome->getIcon())
                ->color($outcome->getColor());
        }

        $stats[] = Stat::make('Schoon achtergelaten', $rate($current) === null ? '-' : $rate($current).'%')
            ->description($rate($previous) === null ? 'Geen uitchecks in de 30 dagen daarvoor' : $rate($previous).'% in de 30 dagen daarvoor')
            ->icon('heroicon-m-chart-bar');

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    protected function counts(Carbon $from, Carbon $until): array
    {
        $counts = [];

        foreach (CleanlinessOutcome::cases() as $outcome) {
            $counts[$outcome->value] = $outcome->scope(ReservationCheck::query())
                ->whereBetween('occurred_at', [$from, $until])
                ->count();
        }

        return $counts;
    }
}
