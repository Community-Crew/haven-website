<?php

return [

    'account_activated' => [
        'subject' => 'Je Haven-account is geactiveerd',
        'greeting' => 'Hoi :name,',
        'body' => 'Je Haven-account is geactiveerd. Je kunt nu inloggen en reserveringen maken.',
    ],

    'reservation_confirmed' => [
        'subject' => 'Reservering bevestigd: :room',
        'greeting' => 'Hoi :name,',
        'body' => 'Je reservering voor :room is bevestigd.',
        'when' => 'Wanneer',
    ],

    'reservation_cancelled' => [
        'subject' => 'Reservering geannuleerd: :room',
        'greeting' => 'Hoi :name,',
        'body' => 'Je reservering voor :room is geannuleerd.',
        'when' => 'Gepland voor',
    ],

    'membership_status_changed' => [
        'subject' => 'Je lidmaatschap is nu :status',
        'greeting' => 'Hoi :name,',
        'body' => 'De status van je Haven-lidmaatschap is gewijzigd naar :status.',
        'status' => [
            'pending' => 'in behandeling',
            'active' => 'actief',
            'suspended' => 'opgeschort',
            'ended' => 'beëindigd',
        ],
    ],

    'footer' => 'Haven Community',

];
