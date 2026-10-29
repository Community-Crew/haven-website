<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Resources\Resource;

/**
 * For a resource reached only via a button on another resource's list, not
 * its own sidebar entry (MemberTypeResource from Memberships,
 * BoardPositionResource from Board Position Assignments - both hide
 * shouldRegisterNavigation()), this restores a way back: "Parent" is
 * prepended to the breadcrumb trail on every page, and getBackToParentAction()
 * gives the list page an explicit "Back to X" button, since arriving here
 * has no sidebar link to retrace.
 */
trait LinksBackToParentResource
{
    /**
     * @return class-string<resource>
     */
    abstract protected static function getBackToResource(): string;

    protected function getBackToParentAction(): Action
    {
        $parent = static::getBackToResource();

        return Action::make('backToParent')
            ->label('Back to '.$parent::getTitleCasePluralModelLabel())
            ->icon('heroicon-o-arrow-left')
            ->color('gray')
            ->url($parent::getUrl('index'));
    }

    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        $parent = static::getBackToResource();

        return [
            $parent::getUrl('index') => $parent::getTitleCasePluralModelLabel(),
            ...parent::getBreadcrumbs(),
        ];
    }
}
