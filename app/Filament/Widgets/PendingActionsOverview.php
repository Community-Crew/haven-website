<?php

namespace App\Filament\Widgets;

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Enums\ReservationStatus;
use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * First thing an admin sees on login - what actually needs a decision today,
 * rather than having to go hunting through each resource to find out.
 */
class PendingActionsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Needs Attention';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 3;

    /**
     * How far back "recent" reaches for the missed-check-outs tile - an
     * auto-rejection from months ago doesn't need fresh attention, but one
     * from yesterday does.
     */
    protected const RECENT_DAYS = 7;

    protected function getStats(): array
    {
        $pendingReservations = Reservation::query()->where('status', ReservationStatus::PENDING)->count();

        $pendingChecks = ReservationCheck::query()->where('verification_status', CheckVerificationStatus::PENDING)->count();
        $flaggedChecks = ReservationCheck::query()
            ->where('verification_status', CheckVerificationStatus::PENDING)
            ->whereNotNull('flag_reason')
            ->count();

        // reviewed_by IS NULL is what distinguishes AutoRejectMissedCheckouts'
        // system rejections from ones an admin actually reviewed by hand.
        $missedCheckouts = ReservationCheck::query()
            ->where('type', CheckType::CHECK_OUT)
            ->where('verification_status', CheckVerificationStatus::REJECTED)
            ->whereNull('reviewed_by')
            ->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))
            ->count();

        return [
            Stat::make('Reservations Awaiting Approval', $pendingReservations)
                ->icon('heroicon-o-clock')
                ->color($pendingReservations > 0 ? 'warning' : 'gray')
                ->url(ReservationResource::getUrl('index')),

            Stat::make('Check-in/out Photos to Review', $pendingChecks)
                ->description($flaggedChecks > 0 ? "{$flaggedChecks} flagged" : null)
                ->descriptionIcon($flaggedChecks > 0 ? 'heroicon-o-flag' : null)
                ->icon('heroicon-o-camera')
                ->color($flaggedChecks > 0 ? 'danger' : ($pendingChecks > 0 ? 'warning' : 'gray'))
                ->url(ReservationCheckResource::getUrl('review')),

            Stat::make('Missed Check-outs (7d)', $missedCheckouts)
                ->description($missedCheckouts > 0 ? 'Auto-rejected, no photo submitted' : null)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($missedCheckouts > 0 ? 'danger' : 'gray')
                ->url(ReservationCheckResource::getUrl('index')),
        ];
    }
}
