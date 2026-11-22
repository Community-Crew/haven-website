<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\UpdateContactDetailsRequest;
use App\Http\Resources\Api\UserProfileResource;
use App\Support\PostalAddress;
use Dedoc\Scramble\Attributes\Group;
use Propaganistas\LaravelPhone\PhoneNumber;

class UserContactDetailsUpdateController extends Controller
{
    /**
     * Update contact details
     *
     * Change the current user's phone number and, for members, birth date.
     */
    #[Group('Users')]
    public function __invoke(UpdateContactDetailsRequest $request): UserProfileResource
    {
        $details = $request->validated();

        if (isset($details['phone'])) {
            $details['phone'] = (new PhoneNumber($details['phone'], 'NL'))->formatE164();
        }

        if (isset($details['postal_address'])) {
            $details['postal_address'] = PostalAddress::normalize($details['postal_address']);
        }

        $user = $request->user();
        $user->update($details);

        return new UserProfileResource($user->load(['unit', 'roles']));
    }
}
