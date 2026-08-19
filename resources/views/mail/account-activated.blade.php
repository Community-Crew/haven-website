@extends('mail.layout')

@section('content')
    <p class="mb-4">{{ __('mail.account_activated.greeting', ['name' => $user->name]) }}</p>
    <p class="text-gray-800">{{ __('mail.account_activated.body') }}</p>
@endsection
