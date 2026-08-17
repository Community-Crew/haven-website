<?php

namespace App\Filament\Resources\Memberships\Schemas;

use App\Enums\MembershipStatus;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\User;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MembershipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Member')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => "{$record->name} ({$record->email})")
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('member_type_id')
                    ->label('Member type')
                    ->relationship('memberType', 'name', fn ($query) => $query->orderBy('sort_order'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state) {
                        if ($state) {
                            $set('has_voting_rights', MemberType::find($state)?->has_voting_rights ?? true);
                        }
                    })
                    ->required(),
                ...self::statusComponents(),
                DatePicker::make('joined_at'),
                DatePicker::make('ended_at')
                    ->after('joined_at'),
                Toggle::make('has_voting_rights')
                    ->default(true),
                TextInput::make('board_role')
                    ->helperText('E.g. Chair, Secretary, Treasurer - leave blank for a regular member.'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Shared by MembershipForm and MembershipsRelationManager: the status
     * field plus the "one open membership per user" validation. $userId
     * lets the relation manager (where the user is fixed and not itself a
     * form field) pass the owner record's id directly instead of reading
     * it via $get('user_id').
     *
     * @return array<int, ToggleButtons>
     */
    public static function statusComponents(?int $userId = null): array
    {
        return [
            ToggleButtons::make('status')
                ->options(collect(MembershipStatus::cases())
                    ->mapWithKeys(fn (MembershipStatus $status) => [$status->value => $status->getLabel()])
                    ->all())
                ->colors(collect(MembershipStatus::cases())
                    ->mapWithKeys(fn (MembershipStatus $status) => [$status->value => $status->getColor()])
                    ->all())
                ->default(MembershipStatus::PENDING->value)
                ->inline()
                ->required()
                ->live()
                ->rule(function (Get $get, ?Membership $record) use ($userId) {
                    return function (string $attribute, $value, Closure $fail) use ($get, $record, $userId) {
                        if (! MembershipStatus::from($value)->isOpen()) {
                            return;
                        }

                        $resolvedUserId = $userId ?? $get('user_id');

                        if (! $resolvedUserId) {
                            return;
                        }

                        if (Membership::hasOpenMembershipFor((int) $resolvedUserId, $record?->getKey())) {
                            $fail('This member already has an open membership (pending, active, or suspended). End it before starting another.');
                        }
                    };
                }),
        ];
    }
}
