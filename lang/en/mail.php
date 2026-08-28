<?php

return [

    'account_activated' => [
        'subject' => 'Your Haven account is active',
        'greeting' => 'Hi :name,',
        'body' => 'Your Haven account has been activated. You can now sign in and start making reservations.',
    ],

    'reservation_confirmed' => [
        'subject' => 'Reservation confirmed: :room',
        'greeting' => 'Hi :name,',
        'body' => 'Your reservation for :room has been confirmed.',
        'when' => 'When',
    ],

    'reservation_cancelled' => [
        'subject' => 'Reservation cancelled: :room',
        'greeting' => 'Hi :name,',
        'body' => 'Your reservation for :room has been cancelled.',
        'when' => 'Was scheduled for',
    ],

    'membership_status_changed' => [
        'subject' => 'Your membership is now :status',
        'greeting' => 'Hi :name,',
        'body' => 'The status of your Haven membership has changed to :status.',
        'status' => [
            'pending' => 'pending',
            'active' => 'active',
            'suspended' => 'suspended',
            'ended' => 'ended',
        ],
    ],

    'privacy_policy_updated' => [
        'subject' => 'Our privacy policy has changed',
        'greeting' => 'Hi :name,',
        'body' => 'We\'ve updated our privacy policy. Please review the new version and accept it next time you sign in to keep using Haven.',
        'action' => 'View the privacy policy',
    ],

    'footer' => 'Haven Community',

];
