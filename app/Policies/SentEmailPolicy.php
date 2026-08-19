<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SentEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Auto-discovered by Laravel's App\Models\X -> App\Policies\XPolicy
 * convention, unlike ActivityPolicy. Read-only on purpose - sent_emails is
 * a log populated by App\Listeners\LogSentEmail, not something to author
 * or delete from the admin panel.
 */
class SentEmailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SentEmail');
    }

    public function view(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return $authUser->can('View:SentEmail');
    }
}
