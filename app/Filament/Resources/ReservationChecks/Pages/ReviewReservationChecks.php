<?php

namespace App\Filament\Resources\ReservationChecks\Pages;

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\ReservationCheck;
use App\Services\ReservationCheckService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Focused review queue: one pending check at a time, flagged ones first
 * (same ordering as the table), with everything needed to judge it on one
 * screen - the submitted photos next to the room's reference photos (and, for
 * a check-out, the same reservation's check-in photos as a before/after), the
 * reservation's time window, the resident's track record and internal notes.
 * Approve/Reject/Skip advance straight to the next one, with A/R/S shortcuts.
 */
class ReviewReservationChecks extends Page
{
    protected static string $resource = ReservationCheckResource::class;

    protected string $view = 'filament.resources.reservation-checks.pages.review-reservation-checks';

    protected static ?string $title = 'Review Check-in/out Photos';

    /**
     * Deep link to a specific check (e.g. from its detail page). Otherwise
     * the page picks the head of the queue.
     */
    #[Url(as: 'check')]
    public ?int $checkId = null;

    #[Url(as: 'flagged')]
    public bool $flaggedOnly = false;

    /**
     * Checks skipped this session - kept out of the queue until the page is
     * reloaded, so Skip actually moves on instead of looping back.
     *
     * @var array<int, int>
     */
    public array $skipped = [];

    public int $reviewedCount = 0;

    /**
     * The check's status as the reviewer saw it when it was put on screen.
     * Models don't survive between Livewire requests, so this is what lets
     * claimCheck() tell "someone else reviewed it since" from "it was
     * already reviewed when I opened it".
     */
    public ?string $loadedStatus = null;

    protected ?ReservationCheck $cachedCheck = null;

    public static function canAccess(array $parameters = []): bool
    {
        return ReservationCheckResource::canViewAny();
    }

    public function mount(): void
    {
        $this->loadCheck();
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title;
    }

    public function getCheck(): ?ReservationCheck
    {
        return $this->cachedCheck ??= $this->checkId
            ? ReservationCheck::query()
                ->with(['photos', 'reviewer', 'reservation.room', 'reservation.user', 'reservation.checks.photos', 'reservation.notes.author'])
                ->find($this->checkId)
            : null;
    }

    public function getPendingCount(): int
    {
        return $this->pendingQuery()->count();
    }

    public function getFlaggedCount(): int
    {
        return $this->pendingQuery()->whereNotNull('flag_reason')->count();
    }

    /**
     * For a check-out, the same reservation's check-in - its photos are the
     * "before" to this check's "after".
     */
    public function getCounterpart(): ?ReservationCheck
    {
        $check = $this->getCheck();

        if (! $check || $check->type !== CheckType::CHECK_OUT) {
            return null;
        }

        return $check->reservation->checks->firstWhere('type', CheckType::CHECK_IN);
    }

    /**
     * The resident's other reviewed checks, newest first - repeat offenders
     * deserve a closer look.
     *
     * @return array{verified: int, rejected: int, missed: int}
     */
    public function getResidentHistory(): array
    {
        $check = $this->getCheck();

        $history = ReservationCheck::query()
            ->whereKeyNot($check->id)
            ->whereHas('reservation', fn (Builder $query) => $query->where('user_id', $check->reservation->user_id))
            ->get(['verification_status', 'reviewed_by', 'type']);

        return [
            'verified' => $history->where('verification_status', CheckVerificationStatus::VERIFIED)->count(),
            'rejected' => $history->where('verification_status', CheckVerificationStatus::REJECTED)->whereNotNull('reviewed_by')->count(),
            // reviewed_by IS NULL on a rejection = AutoRejectMissedCheckouts.
            'missed' => $history->where('verification_status', CheckVerificationStatus::REJECTED)->whereNull('reviewed_by')->count(),
        ];
    }

    public function getNotes(): Collection
    {
        return $this->getCheck()->reservation->notes->sortByDesc('created_at')->take(5)->values();
    }

    /**
     * How far the submission was from the moment it's anchored to (start for
     * a check-in, end for a check-out), as a human-readable phrase.
     */
    public function getTimingLabel(): string
    {
        $check = $this->getCheck();
        $anchor = $check->type === CheckType::CHECK_IN ? $check->reservation->start_at : $check->reservation->end_at;
        $minutes = (int) round(($check->occurred_at->getTimestamp() - $anchor->getTimestamp()) / 60);
        $what = $check->type === CheckType::CHECK_IN ? 'start' : 'end';

        if ($minutes === 0) {
            return "right at the {$what}";
        }

        $amount = abs($minutes) >= 60
            ? sprintf('%dh %02dm', intdiv(abs($minutes), 60), abs($minutes) % 60)
            : abs($minutes).' min';

        return $minutes < 0 ? "{$amount} before the {$what}" : "{$amount} after the {$what}";
    }

    public function getReservationUrl(): string
    {
        return ReservationResource::getUrl('view', ['record' => $this->getCheck()->reservation]);
    }

    public function updatedFlaggedOnly(): void
    {
        $this->checkId = null;
        $this->loadCheck();
    }

    public function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->size('lg')
            ->keyBindings(['a'])
            ->authorize(fn (): bool => $this->canReview())
            ->visible(fn (): bool => $this->getCheck()?->verification_status !== CheckVerificationStatus::VERIFIED)
            ->action(function (ReservationCheckService $service): void {
                if (! $this->claimCheck()) {
                    return;
                }

                $service->approve($this->getCheck(), auth()->user());

                Notification::make()->title('Approved')->success()->send();

                $this->advance();
            });
    }

    public function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->size('lg')
            ->keyBindings(['r'])
            ->authorize(fn (): bool => $this->canReview())
            ->visible(fn (): bool => $this->getCheck()?->verification_status !== CheckVerificationStatus::REJECTED)
            ->modalHeading('Reject this photo')
            ->modalDescription('The resident gets an email with your reason, so write it for them.')
            ->modalSubmitActionLabel('Reject and notify resident')
            ->schema([
                Textarea::make('rejection_note')
                    ->label('Reason')
                    ->placeholder('e.g. The tables weren\'t put back and there are dishes left in the sink.')
                    ->rows(4)
                    ->required()
                    ->autofocus(),
            ])
            ->action(function (array $data, ReservationCheckService $service): void {
                if (! $this->claimCheck()) {
                    return;
                }

                $service->reject($this->getCheck(), auth()->user(), $data['rejection_note']);

                Notification::make()->title('Rejected - the resident has been emailed')->success()->send();

                $this->advance();
            });
    }

    public function skipAction(): Action
    {
        return Action::make('skip')
            ->label('Skip')
            ->icon('heroicon-o-forward')
            ->color('gray')
            ->size('lg')
            ->keyBindings(['s'])
            ->visible(fn (): bool => $this->getCheck()?->verification_status === CheckVerificationStatus::PENDING)
            ->action(function (): void {
                $this->skipped[] = $this->checkId;
                $this->advance(reviewed: false);
            });
    }

    public function resetSkippedAction(): Action
    {
        return Action::make('resetSkipped')
            ->label('Show skipped again')
            ->link()
            ->visible(fn (): bool => $this->skipped !== [])
            ->action(function (): void {
                $this->skipped = [];
                $this->checkId = null;
                $this->loadCheck();
            });
    }

    protected function canReview(): bool
    {
        $check = $this->getCheck();

        return $check !== null && auth()->user()->can('update', $check);
    }

    /**
     * Guards against two admins working the queue at once: if the check's
     * status changed since it was put on screen, someone else reviewed it -
     * don't overwrite their decision, just say so and move on. A check that
     * was already reviewed when opened (via deep link) is fair game though,
     * since re-reviewing it is then a deliberate choice.
     */
    protected function claimCheck(): bool
    {
        $fresh = $this->getCheck()->fresh(['reviewer']);

        if ($fresh->verification_status->value !== $this->loadedStatus) {
            Notification::make()
                ->title('Already reviewed')
                ->body(sprintf('%s marked this as %s in the meantime.', $fresh->reviewer?->name ?? 'Someone', strtolower($fresh->verification_status->getLabel())))
                ->warning()
                ->send();

            $this->advance(reviewed: false);

            return false;
        }

        return true;
    }

    protected function advance(bool $reviewed = true): void
    {
        if ($reviewed) {
            $this->reviewedCount++;
        }

        $this->checkId = null;
        $this->loadCheck();
    }

    protected function loadCheck(): void
    {
        $this->cachedCheck = null;
        $this->checkId ??= $this->pendingQuery()
            ->whereNotIn('id', $this->skipped)
            ->when($this->flaggedOnly, fn (Builder $query) => $query->whereNotNull('flag_reason'))
            ->orderByRaw('flag_reason IS NULL')
            ->orderBy('created_at')
            ->value('id');

        $this->loadedStatus = $this->getCheck()?->verification_status->value;
    }

    protected function pendingQuery(): Builder
    {
        return ReservationCheck::query()->where('verification_status', CheckVerificationStatus::PENDING);
    }
}
