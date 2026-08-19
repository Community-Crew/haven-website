<?php

namespace App\Filament\Widgets;

use App\Models\SentEmail;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SentMailOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Mail';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $today = SentEmail::query()->whereDate('sent_at', today())->count();
        $thisWeek = SentEmail::query()->where('sent_at', '>=', now()->startOfWeek())->count();
        $total = SentEmail::query()->count();

        return [
            Stat::make('Sent Today', $today)
                ->icon('heroicon-o-paper-airplane'),

            Stat::make('Sent This Week', $thisWeek)
                ->icon('heroicon-o-calendar'),

            Stat::make('Sent All Time', $total)
                ->icon('heroicon-o-envelope'),
        ];
    }
}
