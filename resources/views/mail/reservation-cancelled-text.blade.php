{{ __('mail.reservation_cancelled.greeting', ['name' => $reservation->user->name]) }}

{{ __('mail.reservation_cancelled.body', ['room' => $reservation->room->name]) }}

{{ __('mail.reservation_cancelled.when') }}: {{ $reservation->start_at->translatedFormat('j M Y, H:i') }} - {{ $reservation->end_at->translatedFormat('H:i') }}

{{ __('mail.footer') }}
