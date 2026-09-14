<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserRoleSyncService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var array<int, int|string>
     */
    protected array $originalRoleIds = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function fillForm(): void
    {
        parent::fillForm();

        $this->originalRoleIds = $this->getRecord()->roles->pluck('id')->all();
    }

    /**
     * role_ids is dehydrated(false) in UserForm - it never reaches
     * $data/the record's own save, so the diff against the roles the user
     * had when the form loaded has to happen here, with each add/remove
     * routed through UserRoleSyncService (see its docblock for why, not a
     * raw pivot sync).
     */
    protected function afterSave(): void
    {
        // role_ids is dehydrated(false), so getState() strips it entirely
        // (see Filament's Component::dehydrateState()) - getRawState()
        // reads the field's live value straight off the form instead.
        $newRoleIds = $this->form->getRawState()['role_ids'] ?? [];

        $added = array_diff($newRoleIds, $this->originalRoleIds);
        $removed = array_diff($this->originalRoleIds, $newRoleIds);

        if (! $added && ! $removed) {
            return;
        }

        $sync = app(UserRoleSyncService::class);
        $roles = Role::whereIn('id', [...$added, ...$removed])->get()->keyBy('id');

        foreach ($added as $roleId) {
            $sync->addRole($this->record, $roles[$roleId]);
        }

        foreach ($removed as $roleId) {
            $sync->removeRole($this->record, $roles[$roleId]);
        }
    }
}
