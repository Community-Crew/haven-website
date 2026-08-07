<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Activitylog\Models\Activity;

/**
 * Registered manually against Spatie's Activity model in AppServiceProvider,
 * since it lives outside App\Models and Laravel's convention-based policy
 * discovery can't find it. Read-only on purpose - the audit trail isn't
 * meant to be editable or deletable from the admin panel.
 */
class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Activity');
    }

    public function view(AuthUser $authUser, Activity $activity): bool
    {
        return $authUser->can('View:Activity');
    }
}
