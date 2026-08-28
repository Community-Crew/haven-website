@extends('mail.layout')

@section('content')
    <p class="mb-4">{{ __('mail.privacy_policy_updated.greeting', ['name' => $user->name]) }}</p>
    <p class="mb-4 text-gray-800">{{ __('mail.privacy_policy_updated.body') }}</p>
    <p class="text-gray-800">
        <a href="{{ $url }}">{{ __('mail.privacy_policy_updated.action') }}</a>
    </p>
@endsection
