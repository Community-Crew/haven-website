<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserSettingsResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

class UserSettingsShowController extends Controller
{
    /**
     * View settings
     *
     * Get the current user's app settings, so they follow the user across
     * devices instead of living in one device's local storage.
     */
    #[Group('Users')]
    public function __invoke(Request $request): UserSettingsResource
    {
        return new UserSettingsResource($request->user());
    }
}
