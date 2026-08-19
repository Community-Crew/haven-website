{{ __('mail.reservation_confirmed.greeting', ['name' => $reservation->user->name]) }}

{{ __('mail.reservation_confirmed.body', ['room' => $reservation->room->name]) }}

{{ __('mail.reservation_confirmed.when') }}: {{ $reservation->start_at->translatedFormat('j M Y, H:i') }} - {{ $reservation->end_at->translatedFormat('H:i') }}

{{ __('mail.footer') }}
