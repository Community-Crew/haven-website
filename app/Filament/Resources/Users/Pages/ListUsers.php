<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\MembershipStatus;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Membership;
use App\Models\MembershipDocument;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Members & users';

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('memberTypes')
                ->label('Member types')
                ->icon(Heroicon::Tag)
                ->color('gray')
                ->url(fn () => MemberTypeResource::getUrl('index'))
                ->visible(fn () => MemberTypeResource::canViewAny()),
            Action::make('membershipDocuments')
                ->label('Statutes & house rules')
                ->icon(Heroicon::DocumentText)
                ->color('gray')
                ->visible(fn () => (bool) Auth::user()?->can('Update:Membership'))
                ->modalWidth(Width::FiveExtraLarge)
                ->modalDescription('Residents accept these when applying. Changing the text saves a new version; earlier acceptances keep pointing at the version they accepted.')
                ->fillForm(fn () => [
                    'statutes' => MembershipDocument::current(MembershipDocument::STATUTES)?->content,
                    'rules' => MembershipDocument::current(MembershipDocument::RULES)?->content,
                ])
                ->schema([
                    RichEditor::make('statutes')->label('Statutes')->required(),
                    RichEditor::make('rules')->label('House rules (huishoudelijk reglement)')->required(),
                ])
                ->action(function (array $data) {
                    MembershipDocument::publish(MembershipDocument::STATUTES, $data['statutes'], Auth::user());
                    MembershipDocument::publish(MembershipDocument::RULES, $data['rules'], Auth::user());

                    Notification::make()->title('Statutes and house rules saved')->success()->send();
                }),
        ];
    }

    /**
     * "Possibly moved out" is the review list for residents who left without
     * a Vestide import catching it (see User::scopePossiblyMovedOut) - an
     * admin confirms each with "Mark as moved out", which emails them.
     */
    public function getTabs(): array
    {
        $possiblyMovedOut = User::possiblyMovedOut()->count();

        return [
            'all' => Tab::make('All'),
            'applications' => Tab::make('Applications')
                // Only what the board can act on; the rest waits on the applicant.
                ->badge(fn () => Membership::readyForReview()->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->whereHas('memberships', fn (Builder $membership) => $membership->where('status', MembershipStatus::PENDING))
                    ->orderByDesc(Membership::query()
                        ->selectRaw('documents_accepted_at is not null')
                        ->whereColumn('memberships.user_id', 'users.id')
                        ->where('status', MembershipStatus::PENDING)
                        ->limit(1))),
            // Ereleden and begunstigers are a filter within this tab.
            'members' => Tab::make('Members')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('memberships', fn (Builder $membership) => $membership
                    ->whereIn('status', [MembershipStatus::ACTIVE, MembershipStatus::SUSPENDED]))),
            'residents' => Tab::make('Residents')
                ->modifyQueryUsing(fn (Builder $query) => $query->currentResidents()),
            // Only there when there's something to review. Stays while it's
            // open, so handling the last person doesn't pull the tab away.
            ...($possiblyMovedOut > 0 || $this->activeTab === 'possibly_moved_out' ? [
                'possibly_moved_out' => Tab::make('Possibly moved out')
                    ->badge($possiblyMovedOut ?: null)
                    ->badgeColor('warning')
                    ->modifyQueryUsing(fn (Builder $query) => $query->possiblyMovedOut()),
            ] : []),
            'moved_out' => Tab::make('Moved out')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('moved_out_at')),
        ];
    }
}
