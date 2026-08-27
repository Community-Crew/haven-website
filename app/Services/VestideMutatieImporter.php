<?php

namespace App\Services;

use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Parses Vestide's monthly "mutaties" export (new tenant contracts, one
 * "Actief vanaf" date + "Contractomschrijving" address per row) and turns
 * each row into a registration code for the matching unit, after clearing
 * out whoever is currently assigned there.
 *
 * Vestide addresses look like "Blauwe loper 66 0709 /D, 5612 TA, EINDHOVEN,
 * NL": street numbers 60 and 66 are the two towers (Castor/Pollux), whose
 * apartment number packs floor+unit into 4 digits with an optional "/letter"
 * subunit suffix. Street numbers 61-65 are Terra, whose own "floor" column
 * already *is* the street number, with unit/subunit as single letters.
 */
class VestideMutatieImporter
{
    private const TOWERS = [
        '60' => 'Castor',
        '66' => 'Pollux',
    ];

    private const TERRA_STREET_NUMBERS = ['61', '62', '63', '64', '65'];

    /**
     * Parse raw pasted text into rows, matched against existing Units where
     * possible. Read-only - nothing is changed in the database yet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function preview(string $raw): array
    {
        return $this->parseLines($raw)
            ->map(fn (array $row) => $this->matchRow($row))
            ->values()
            ->all();
    }

    /**
     * Apply previously-previewed rows: for every row with a matched unit
     * that wasn't skipped, unassign/deactivate its current resident(s) and
     * issue a fresh registration code.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function apply(array $rows): array
    {
        return collect($rows)->map(function (array $row) {
            if (! $row['unit_id'] || ($row['skip'] ?? false) || ($row['applied'] ?? false)) {
                return $row;
            }

            $unit = Unit::find($row['unit_id']);

            if (! $unit) {
                $row['error'] = 'Unit no longer exists.';
                $row['skip'] = true;

                return $row;
            }

            // Wrapped in one transaction per row so a failure issuing the new
            // code (or anything else) can't leave a unit's outgoing resident
            // unassigned with no replacement code - it rolls back and the
            // row is reported as an error instead, retryable on its own.
            try {
                $code = DB::transaction(function () use ($unit) {
                    $unit->users()->update(['unit_id' => null, 'activated_at' => null]);

                    return $unit->registrationCodes()->create([]);
                });
            } catch (Throwable $e) {
                report($e);

                $row['error'] = 'Failed to apply this row: '.$e->getMessage();

                return $row;
            }

            $row['registration_code'] = $code->code;
            $row['registration_code_id'] = $code->id;
            $row['printed_at'] = null;
            $row['current_residents'] = [];
            $row['applied'] = true;

            return $row;
        })->values()->all();
    }

    /**
     * @return Collection<int, array{date: ?string, address: string}>
     */
    private function parseLines(string $raw): Collection
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->filter(fn ($line) => ! str_starts_with(mb_strtolower($line), 'actief vanaf'))
            ->filter(fn ($line) => ! str_starts_with(mb_strtolower($line), 'contractomschrijving'))
            ->values();

        $rows = collect();
        $pendingDate = null;

        foreach ($lines as $line) {
            if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $line)) {
                $pendingDate = $line;

                continue;
            }

            if (! str_contains(mb_strtolower($line), 'blauwe loper')) {
                continue;
            }

            $rows->push([
                'date' => $pendingDate,
                'address' => $line,
            ]);

            $pendingDate = null;
        }

        return $rows;
    }

    /**
     * @param  array{date: ?string, address: string}  $row
     * @return array<string, mixed>
     */
    private function matchRow(array $row): array
    {
        $result = [
            'date' => $row['date'] ? Carbon::createFromFormat('j-n-Y', $row['date'])->toDateString() : null,
            'address' => $row['address'],
            'unit_id' => null,
            'unit_name' => null,
            'current_residents' => [],
            'error' => null,
            'skip' => false,
            'applied' => false,
        ];

        if (! preg_match('/blauwe\s+loper\s+(\d+)\s+(.+?),/i', $row['address'], $matches)) {
            $result['error'] = 'Could not parse address.';
            $result['skip'] = true;

            return $result;
        }

        $streetNumber = trim($matches[1]);
        $apartment = trim($matches[2]);

        $unit = in_array($streetNumber, self::TERRA_STREET_NUMBERS, true)
            ? $this->matchTerraUnit($streetNumber, $apartment)
            : $this->matchTowerUnit($streetNumber, $apartment);

        if (! $unit) {
            $result['error'] = "No matching unit found for \"{$row['address']}\".";
            $result['skip'] = true;

            return $result;
        }

        $result['unit_id'] = $unit->id;
        $result['unit_name'] = $unit->name;
        $result['current_residents'] = $unit->users()->pluck('name')->all();

        return $result;
    }

    private function matchTerraUnit(string $streetNumber, string $apartment): ?Unit
    {
        if (! preg_match('/^([A-Za-z])(?:\s*\/\s*([A-Za-z]))?$/', $apartment, $m)) {
            return null;
        }

        return Unit::query()
            ->where('building', 'Terra')
            ->where('floor', (int) $streetNumber)
            ->where('unit', strtoupper($m[1]))
            ->where('subunit', isset($m[2]) ? strtoupper($m[2]) : '')
            ->first();
    }

    private function matchTowerUnit(string $streetNumber, string $apartment): ?Unit
    {
        $building = self::TOWERS[$streetNumber] ?? null;

        if (! $building) {
            return null;
        }

        if (! preg_match('/^(\d{2})(\d{2})(?:\s*\/\s*([A-Za-z]))?$/', $apartment, $m)) {
            return null;
        }

        return Unit::query()
            ->where('building', $building)
            ->where('floor', (int) $m[1])
            ->where('unit', (string) (int) $m[2])
            ->where('subunit', isset($m[3]) ? strtoupper($m[3]) : '')
            ->first();
    }
}
