<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateUserSettingsRequest;
use App\Http\Resources\Api\UserSettingsResource;
use Dedoc\Scramble\Attributes\Group;

class UserSettingsUpdateController extends Controller
{
    /**
     * Update settings
     *
     * Change one or more of the current user's app settings. Settings left
     * out of the request keep their current value. The locale is also the
     * language mails to this user are sent in.
     */
    #[Group('Users')]
    public function __invoke(UpdateUserSettingsRequest $request): UserSettingsResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserSettingsResource($user);
    }
}
