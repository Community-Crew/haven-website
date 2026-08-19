<?php

use App\Mail\MembershipStatusChangedMail;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'member@example.com',
        'keycloak_id' => 'kc-member',
        'locale' => 'nl',
    ]);

    $this->memberType = MemberType::create(['name' => 'Regular']);
});

it('mails the member when their status changes', function () {
    Mail::fake();

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'pending',
    ]);

    $membership->update(['status' => 'active']);

    Mail::assertSent(
        MembershipStatusChangedMail::class,
        fn (MembershipStatusChangedMail $mail) => $mail->hasTo($this->user->email)
    );
});

it('does not mail when nothing but status stays the same', function () {
    Mail::fake();

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'pending',
    ]);

    $membership->update(['notes' => 'Reached out about renewing.']);

    Mail::assertNotSent(MembershipStatusChangedMail::class);
});
