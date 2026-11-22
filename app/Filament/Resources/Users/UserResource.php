<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\BoardPositionAssignmentsRelationManager;
use App\Filament\Resources\Users\RelationManagers\MembershipsRelationManager;
use App\Filament\Resources\Users\RelationManagers\ReservationsRelationManager;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\Membership;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|null|\UnitEnum $navigationGroup = 'Members';

    protected static ?string $navigationLabel = 'Members & users';

    protected static ?string $breadcrumb = 'Members & users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $applications = Membership::readyForReview()->count();

        return $applications > 0 ? (string) $applications : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MembershipsRelationManager::class,
            BoardPositionAssignmentsRelationManager::class,
            ReservationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Members';
    }
}
