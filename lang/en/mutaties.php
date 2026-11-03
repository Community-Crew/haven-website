<?php

// Vestide mutaties import (ImportVestideMutaties). The admin panel shows it in
// the app locale (English); the Vestide panel forces Dutch.
return [
    'title' => 'Import Vestide Mutaties',
    'navigation' => 'Import Mutaties',
    'field' => 'Vestide mutaties list',
    'field_help' => 'Paste the "Actief vanaf" / "Contractomschrijving" table straight from the Vestide export.',
    'preview' => 'Preview',
    'apply' => 'Apply mutaties',
    'apply_confirm' => 'This unassigns and deactivates the current resident(s) of every matched unit and issues a new registration code for each. This cannot be undone automatically.',
    'matched' => 'Matched :matched of :total rows',
    'applied' => 'Applied :count mutation(s)',
    'applied_body' => 'New registration codes were generated for each matched unit; the previous resident(s) were unassigned and deactivated.',
    'columns' => [
        'active_from' => 'Active from',
        'address' => 'Address',
        'unit' => 'Unit',
        'residents' => 'Current resident(s)',
        'status' => 'Status',
        'new_code' => 'New code',
    ],
    'vacant' => 'vacant',
    'match_ok' => 'Matched',
    'errors' => [
        'unit_gone' => 'Unit no longer exists.',
        'apply_failed' => 'Failed to apply this row: :message',
        'unparseable' => 'Could not parse address.',
        'no_unit' => 'No matching unit found for ":address".',
    ],
];
