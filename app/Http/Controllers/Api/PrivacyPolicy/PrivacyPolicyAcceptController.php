<?php

namespace App\Http\Controllers\Api\PrivacyPolicy;

use App\Http\Resources\Api\UserProfileResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

/**
 * Accept the privacy policy
 *
 * Record that the current user accepts the privacy policy as it currently
 * reads. Required once per user, and again any time the policy is edited -
 * see EnsureUserAcceptedPrivacyPolicy.
 *
 * @return UserProfileResource
 */
class PrivacyPolicyAcceptController
{
    #[Group('Privacy Policy')]
    public function __invoke(Request $request): UserProfileResource
    {
        $user = $request->user();
        $user->update(['privacy_policy_accepted_at' => now()]);

        return new UserProfileResource($user);
    }
}
