@extends('mail.layout')

@section('content')
    <p class="mb-4">{{ __('mail.reservation_check_rejected.greeting', ['name' => $check->reservation->user->name]) }}</p>
    <p class="text-gray-800 mb-4">
        {{ __('mail.reservation_check_rejected.body', [
            'type' => __('mail.reservation_check_rejected.type.'.$check->type->value),
            'room' => $check->reservation->room->name,
        ]) }}
    </p>
    @if ($check->rejection_note)
        <p class="text-sm text-gray-600">
            <span class="font-semibold text-haven-red">{{ __('mail.reservation_check_rejected.reason') }}:</span>
            {{ $check->rejection_note }}
        </p>
    @endif
@endsection
