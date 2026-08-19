<?php

namespace App\Filament\Widgets;

use App\Models\Unit;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverRegistrationOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Registration';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalUnits = Unit::count();

        $overRegisteredCount = Unit::whereRaw('
            max_residents < (
                SELECT COUNT(*) FROM users
                WHERE users.unit_id = units.id
            )
        ')->count();

        return [
            Stat::make('Total Units', $totalUnits)
                ->icon('heroicon-o-home'),

            Stat::make('Active Users', $totalUsers)
                ->description('Current active users')
                ->icon('heroicon-o-users'),

            Stat::make('Over-Registered Units', $overRegisteredCount)
                ->description($overRegisteredCount > 0 ? 'Requires attention!' : 'All normal!')
                ->icon($overRegisteredCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($overRegisteredCount > 0 ? 'danger' : 'success')
                ->extraAttributes([
                    'class' => $overRegisteredCount > 0 ? 'animate-pulse font-bold' : '',
                ]),
        ];
    }
}
