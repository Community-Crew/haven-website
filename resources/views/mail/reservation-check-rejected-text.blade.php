{{ __('mail.reservation_check_rejected.greeting', ['name' => $check->reservation->user->name]) }}

{{ __('mail.reservation_check_rejected.body', [
    'type' => __('mail.reservation_check_rejected.type.'.$check->type->value),
    'room' => $check->reservation->room->name,
]) }}
@if ($check->rejection_note)

{{ __('mail.reservation_check_rejected.reason') }}: {{ $check->rejection_note }}
@endif
@if ($check->type === \App\Enums\CheckType::CHECK_OUT)

{{ __('mail.reservation_check_rejected.shared_with_vestide') }}
@endif

{{ __('mail.footer') }}
