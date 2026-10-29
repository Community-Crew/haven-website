<?php

namespace App\Filament\Resources\BoardPositionAssignments\Pages;

use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use App\Filament\Resources\BoardPositions\BoardPositionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListBoardPositionAssignments extends ListRecords
{
    protected static string $resource = BoardPositionAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('boardPositions')
                ->label('Board Positions')
                ->icon(Heroicon::Identification)
                ->color('gray')
                ->url(fn () => BoardPositionResource::getUrl('index'))
                ->visible(fn () => BoardPositionResource::canViewAny()),
            CreateAction::make(),
        ];
    }
}
