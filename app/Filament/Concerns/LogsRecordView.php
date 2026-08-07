<?php

namespace App\Filament\Concerns;

/**
 * Drop onto a Filament ViewRecord page to record an audit-log entry each
 * time an admin opens a record's view page. Logged under the same default
 * log used by model CRUD (see App\Traits\Auditable), so a record's full
 * history - created/updated/deleted/viewed - shows up in one timeline.
 */
trait LogsRecordView
{
    public function mount(int|string $record): void
    {
        parent::mount($record);

        activity()
            ->performedOn($this->getRecord())
            ->event('viewed')
            ->withProperties(['ip' => request()->ip()])
            ->log('viewed');
    }
}
