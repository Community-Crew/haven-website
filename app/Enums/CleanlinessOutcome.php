<?php

namespace App\Enums;

use App\Models\ReservationCheck;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;

/**
 * How a hangout was left after a reservation, as reported to Vestide. Derived
 * from a reviewed check-out rather than stored: verified = clean, rejected
 * by an admin = dirty, rejected with no reviewer = ghosted (the resident
 * never checked out and AutoRejectMissedCheckouts filled the gap). Pending
 * check-outs haven't been judged yet and have no outcome.
 *
 * Labels are Dutch: the enum only surfaces in the (Dutch-only) Vestide panel.
 *
 * If rejection reasons ever get tags (dishes, furniture, ...), they'd refine
 * DIRTY rather than add cases here.
 */
enum CleanlinessOutcome: string implements HasColor, HasIcon, HasLabel
{
    case CLEAN = 'clean';
    case DIRTY = 'dirty';
    case GHOSTED = 'ghosted';

    public static function forCheck(ReservationCheck $check): ?self
    {
        return match ($check->verification_status) {
            CheckVerificationStatus::VERIFIED => self::CLEAN,
            CheckVerificationStatus::REJECTED => $check->reviewed_by === null ? self::GHOSTED : self::DIRTY,
            default => null,
        };
    }

    /**
     * Narrow a ReservationCheck query to check-outs with this outcome.
     */
    public function scope(Builder $query): Builder
    {
        // Qualified, since this also runs inside User::reservationChecks()
        // counts, where reservations is joined in.
        $type = $query->qualifyColumn('type');
        $status = $query->qualifyColumn('verification_status');
        $reviewer = $query->qualifyColumn('reviewed_by');

        $query->where($type, CheckType::CHECK_OUT);

        return match ($this) {
            self::CLEAN => $query->where($status, CheckVerificationStatus::VERIFIED),
            self::DIRTY => $query->where($status, CheckVerificationStatus::REJECTED)->whereNotNull($reviewer),
            self::GHOSTED => $query->where($status, CheckVerificationStatus::REJECTED)->whereNull($reviewer),
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CLEAN => 'Schoon',
            self::DIRTY => 'Vies achtergelaten',
            self::GHOSTED => 'Niet uitgecheckt',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CLEAN => 'success',
            self::DIRTY => 'danger',
            self::GHOSTED => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::CLEAN => 'heroicon-m-sparkles',
            self::DIRTY => 'heroicon-m-exclamation-triangle',
            self::GHOSTED => 'heroicon-m-question-mark-circle',
        };
    }
}
