<?php

namespace App\Filament\Resources\Reservations\Pages;

use App\Enums\CheckVerificationStatus;
use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Filament\Support\ReservationPageActions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            ReservationPageActions::reviewPhotos(),
            ReservationPageActions::allPhotos(),
            Action::make('agenda')
                ->label('Agenda')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('gray')
                ->url(ReservationResource::getUrl('index')),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            ...(ReservationCheckResource::canViewAny() ? [
                'to_review' => Tab::make('To review')
                    ->badge(fn () => ReservationCheckResource::getNavigationBadge())
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('checks', fn (Builder $check) => $check
                        ->where('verification_status', CheckVerificationStatus::PENDING))),
            ] : []),
            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('end_at', '>=', now())),
            'past' => Tab::make('Past')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('end_at', '<', now())),
        ];
    }
}
