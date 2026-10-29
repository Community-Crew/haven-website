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

    'reservation_check_rejected' => [
        'subject' => 'Je in-/uitcheckfoto is nog niet goedgekeurd: :room',
        'greeting' => 'Hoi :name,',
        'body' => 'Je :type-foto voor :room is niet goedgekeurd.',
        'reason' => 'Reden',
        'type' => [
            'check_in' => 'incheck',
            'check_out' => 'uitcheck',
        ],
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

    'privacy_policy_updated' => [
        'subject' => 'Ons privacybeleid is gewijzigd',
        'greeting' => 'Hoi :name,',
        'body' => 'We hebben ons privacybeleid aangepast. Bekijk de nieuwe versie en accepteer deze de volgende keer dat je inlogt om Haven te blijven gebruiken.',
        'action' => 'Bekijk het privacybeleid',
    ],

    'resident_moved_out' => [
        'subject' => 'Je Haven-account wordt verwijderd',
        'greeting' => 'Hoi :name,',
        'body' => 'Volgens onze gegevens woon je niet meer in Haven, dus is je Haven-account gesloten. Bedankt dat je deel uitmaakte van de community!',
        'deletion' => 'Op :date verwijderen we je account en persoonsgegevens definitief. Je eerdere reserveringen bewaren we alleen als anonieme statistiek.',
        'mistake' => 'Woon je hier nog wel? Dan is dit een vergissing - beantwoord deze mail vóór :date en we lossen het op.',
    ],

    'footer' => 'Haven Community',

];
