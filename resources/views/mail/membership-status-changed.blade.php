@extends('mail.layout')

@section('content')
    <p class="mb-4">{{ __('mail.membership_status_changed.greeting', ['name' => $membership->user->name]) }}</p>
    <p class="text-gray-800">{{ __('mail.membership_status_changed.body', ['status' => __('mail.membership_status_changed.status.'.$membership->status->value)]) }}</p>
@endsection
