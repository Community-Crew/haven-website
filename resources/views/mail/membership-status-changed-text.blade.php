{{ __('mail.membership_status_changed.greeting', ['name' => $membership->user->name]) }}

{{ __('mail.membership_status_changed.body', ['status' => __('mail.membership_status_changed.status.'.$membership->status->value)]) }}

{{ __('mail.footer') }}
