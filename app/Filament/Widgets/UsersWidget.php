<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UsersWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::all()->count())
                ->description('Max allowed users: 819')
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before),
        ];
    }
}
