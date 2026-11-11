<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\FaqCategory;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class FaqCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:FaqCategory');
    }

    public function view(AuthUser $authUser, FaqCategory $faqCategory): bool
    {
        return $authUser->can('View:FaqCategory');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:FaqCategory');
    }

    public function update(AuthUser $authUser, FaqCategory $faqCategory): bool
    {
        return $authUser->can('Update:FaqCategory');
    }

    public function delete(AuthUser $authUser, FaqCategory $faqCategory): bool
    {
        return $authUser->can('Delete:FaqCategory');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:FaqCategory');
    }

    public function restore(AuthUser $authUser, FaqCategory $faqCategory): bool
    {
        return $authUser->can('Restore:FaqCategory');
    }

    public function forceDelete(AuthUser $authUser, FaqCategory $faqCategory): bool
    {
        return $authUser->can('ForceDelete:FaqCategory');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:FaqCategory');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:FaqCategory');
    }

    public function replicate(AuthUser $authUser, FaqCategory $faqCategory): bool
    {
        return $authUser->can('Replicate:FaqCategory');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:FaqCategory');
    }
}
