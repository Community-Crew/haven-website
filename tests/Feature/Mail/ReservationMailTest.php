<?php

use App\Mail\ReservationCancelledMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'reserver@example.com',
        'keycloak_id' => 'kc-reserver',
        'locale' => 'nl',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);
});

it('mentions the room and mails the reservation owner on confirmation', function () {
    Mail::fake();

    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHours(2),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    Mail::to($reservation->user)->send(new ReservationConfirmedMail($reservation));

    Mail::assertSent(
        ReservationConfirmedMail::class,
        fn (ReservationConfirmedMail $mail) => $mail->hasTo($this->user->email)
            && str_contains($mail->render(), 'Werkplaats')
    );
});

it('mentions the room and mails the reservation owner on cancellation', function () {
    Mail::fake();

    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHours(2),
        'status' => 'cancelled',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    Mail::to($reservation->user)->send(new ReservationCancelledMail($reservation));

    Mail::assertSent(
        ReservationCancelledMail::class,
        fn (ReservationCancelledMail $mail) => $mail->hasTo($this->user->email)
            && str_contains($mail->render(), 'Werkplaats')
    );
});
