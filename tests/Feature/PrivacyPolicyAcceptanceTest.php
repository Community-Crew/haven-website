<?php

use App\Http\Controllers\Api\PrivacyPolicy\PrivacyPolicyAcceptController;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Models\PrivacyPolicy;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

// These exercise the middleware/controller directly rather than through the
// route (which sits behind ValidateKeycloakToken's real Keycloak JWT check,
// not something this suite fakes - see the other API feature tests).

function makePrivacyPolicyTestUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'email' => fake()->unique()->safeEmail(),
        'keycloak_id' => 'kc-'.fake()->unique()->uuid(),
    ], $attributes));
}

it('has not accepted the current policy by default', function () {
    $user = makePrivacyPolicyTestUser();

    expect($user->hasAcceptedCurrentPrivacyPolicy())->toBeFalse();
});

it('has accepted the current policy once accepted_at is at or after the policy was last updated', function () {
    $policy = PrivacyPolicy::current();
    $user = makePrivacyPolicyTestUser(['privacy_policy_accepted_at' => $policy->updated_at]);

    expect($user->hasAcceptedCurrentPrivacyPolicy())->toBeTrue();
});

it('requires re-acceptance once the policy is edited after a user already accepted it', function () {
    $user = makePrivacyPolicyTestUser(['privacy_policy_accepted_at' => now()]);

    expect($user->hasAcceptedCurrentPrivacyPolicy())->toBeTrue();

    Carbon::setTestNow(now()->addMinute());
    PrivacyPolicy::current()->update(['content' => '<p>New terms.</p>']);
    Carbon::setTestNow();

    expect($user->fresh()->hasAcceptedCurrentPrivacyPolicy())->toBeFalse();
});

it('blocks requests via EnsureUserAcceptedPrivacyPolicy until the user accepts', function () {
    $user = makePrivacyPolicyTestUser();
    $request = Request::create('/api/v1/reservations/me');
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureUserAcceptedPrivacyPolicy)->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(Response::HTTP_FORBIDDEN)
        ->and($response->getData(true)['errors'])->toBe('PRIVACY_POLICY_NOT_ACCEPTED');
});

it('lets requests through EnsureUserAcceptedPrivacyPolicy once accepted', function () {
    $user = makePrivacyPolicyTestUser(['privacy_policy_accepted_at' => now()]);
    $request = Request::create('/api/v1/reservations/me');
    $request->setUserResolver(fn () => $user);

    $response = (new EnsureUserAcceptedPrivacyPolicy)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('records acceptance via PrivacyPolicyAcceptController', function () {
    $user = makePrivacyPolicyTestUser();
    $request = Request::create('/api/v1/privacy-policy/accept', 'POST');
    $request->setUserResolver(fn () => $user);

    (new PrivacyPolicyAcceptController)->__invoke($request);

    expect($user->fresh()->hasAcceptedCurrentPrivacyPolicy())->toBeTrue();
});
