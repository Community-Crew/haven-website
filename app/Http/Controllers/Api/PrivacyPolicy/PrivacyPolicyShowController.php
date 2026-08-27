<?php

namespace App\Http\Controllers\Api\PrivacyPolicy;

use App\Http\Resources\Api\PrivacyPolicyResource;
use App\Models\PrivacyPolicy;
use Dedoc\Scramble\Attributes\Group;

/**
 * View the privacy policy
 *
 * Retrieve the current privacy policy content. Public - no authentication
 * required, since this is a legal page residents (and prospective residents)
 * must be able to read before logging in.
 *
 * @return PrivacyPolicyResource
 */
class PrivacyPolicyShowController
{
    #[Group('Privacy Policy')]
    public function __invoke(): PrivacyPolicyResource
    {
        return new PrivacyPolicyResource(PrivacyPolicy::current());
    }
}
