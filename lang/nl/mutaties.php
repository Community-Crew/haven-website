<?php

return [
    'title' => 'Mutaties importeren',
    'navigation' => 'Mutaties importeren',
    'field' => 'Mutatielijst van Vestide',
    'field_help' => 'Plak de tabel "Actief vanaf" / "Contractomschrijving" rechtstreeks uit de Vestide-export.',
    'preview' => 'Voorbeeld',
    'apply' => 'Mutaties doorvoeren',
    'apply_confirm' => 'De huidige bewoner(s) van elke gevonden unit worden uitgeschreven en gedeactiveerd, en voor elke unit wordt een nieuwe registratiecode aangemaakt. Dit kan niet automatisch worden teruggedraaid.',
    'matched' => ':matched van :total regels gevonden',
    'applied' => ':count mutatie(s) doorgevoerd',
    'applied_body' => 'Voor elke gevonden unit is een nieuwe registratiecode aangemaakt; de vorige bewoner(s) zijn uitgeschreven en gedeactiveerd.',
    'columns' => [
        'active_from' => 'Actief vanaf',
        'address' => 'Adres',
        'unit' => 'Unit',
        'residents' => 'Huidige bewoner(s)',
        'status' => 'Status',
        'new_code' => 'Nieuwe code',
    ],
    'vacant' => 'leeg',
    'match_ok' => 'Gevonden',
    'errors' => [
        'unit_gone' => 'Deze unit bestaat niet meer.',
        'apply_failed' => 'Deze regel kon niet worden doorgevoerd: :message',
        'unparseable' => 'Adres kon niet worden gelezen.',
        'no_unit' => 'Geen unit gevonden voor ":address".',
    ],
];
