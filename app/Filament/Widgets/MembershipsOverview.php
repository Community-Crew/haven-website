<?php

namespace App\Filament\Widgets;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MembershipsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Memberships';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 3;

    protected function getStats(): array
    {
        $active = Membership::query()->where('status', MembershipStatus::ACTIVE)->count();
        $pending = Membership::query()->where('status', MembershipStatus::PENDING)->count();
        $suspended = Membership::query()->where('status', MembershipStatus::SUSPENDED)->count();

        return [
            Stat::make('Active Members', $active)
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Pending Applications', $pending)
                ->icon('heroicon-o-clock')
                ->color($pending > 0 ? 'warning' : 'gray'),

            Stat::make('Suspended', $suspended)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($suspended > 0 ? 'danger' : 'gray'),
        ];
    }
}
