@extends('mail.layout')

@section('content')
    <p class="mb-4">{{ __('mail.resident_moved_out.greeting', ['name' => $user->name]) }}</p>
    <p class="text-gray-800 mb-4">{{ __('mail.resident_moved_out.body') }}</p>
    <p class="text-gray-800 mb-4">{{ __('mail.resident_moved_out.deletion', ['date' => $date]) }}</p>
    <p class="text-sm text-gray-600">{{ __('mail.resident_moved_out.mistake', ['date' => $date]) }}</p>
@endsection
