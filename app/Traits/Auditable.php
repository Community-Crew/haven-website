<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * App-wide defaults on top of Spatie's LogsActivity: every attribute is
 * tracked, only actual changes produce a log entry, and no-op saves don't
 * write anything. Applied to every domain model so create/update/delete is
 * uniformly audited - see the `activity_log` table and ActivityLogResource.
 *
 * Models holding secrets (tokens, etc.) should override auditExcept() to
 * keep those values out of the log rather than relying on $hidden, which
 * only affects serialization, not what gets recorded here.
 */
trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept($this->auditExcept())
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function auditExcept(): array
    {
        return ['updated_at'];
    }
}
