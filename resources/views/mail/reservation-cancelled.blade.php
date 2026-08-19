@extends('mail.layout')

@section('content')
    <p class="mb-4">{{ __('mail.reservation_cancelled.greeting', ['name' => $reservation->user->name]) }}</p>
    <p class="text-gray-800 mb-4">{{ __('mail.reservation_cancelled.body', ['room' => $reservation->room->name]) }}</p>
    <p class="text-sm text-gray-600">
        <span class="font-semibold text-haven-red">{{ __('mail.reservation_cancelled.when') }}:</span>
        {{ $reservation->start_at->translatedFormat('j M Y, H:i') }}
        &ndash;
        {{ $reservation->end_at->translatedFormat('H:i') }}
    </p>
@endsection
