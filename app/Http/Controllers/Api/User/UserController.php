<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserProfileResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * View current user
     *
     * Get the current authenticated user's profile, including their unit and assigned roles.
     */
    #[Group('Users')]
    public function __invoke(Request $request): UserProfileResource
    {
        $user = $request->user()->load(['unit', 'roles']);

        return new UserProfileResource($user);
    }
}
