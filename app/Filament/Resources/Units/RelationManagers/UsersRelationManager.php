<?php

namespace App\Filament\Resources\Units\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'Users';

    protected static ?string $relatedResource = UserResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('assignUser')
                    ->label('Assign User')
                    ->modalHeading('Assign User to Unit')
                    ->modalSubmitActionLabel('Assign')
                    ->icon('heroicon-o-user-plus')
                    ->schema([
                        Select::make('user_id')
                            ->label('Select User')
                            ->placeholder('Type to search users...')
                            ->getSearchResultsUsing(fn(string $search): array => User::where('name', 'ilike', "%{$search}%")
                                ->orWhere('email', 'ilike', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelUsing(fn(string $value): ?string => User::find($value)?->name)
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $unit = $livewire->getOwnerRecord();

                        $user = User::findOrFail($data['user_id']);
                        $user->unit_id = $unit->id;
                        $user->save();

                        Notification::make()
                            ->title('User assigned successfully')
                            ->success()
                            ->send();
                    }),
            ])->recordActions([
                Action::make('unassign')
                    ->label('Remove')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(fn(User $record) => $record->update(['unit_id' => null])),
            ])->searchable(false)
            ->columnManager(false)
            ->paginated(false);
    }
}
