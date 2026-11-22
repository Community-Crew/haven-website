<?php

namespace App\Support;

/**
 * The postal address of a former resident (beneficiary), who no longer has
 * a unit to derive one from.
 */
class PostalAddress
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(string $presence): array
    {
        return [
            'postal_address' => [$presence, 'array:line,postal_code,city,country'],
            'postal_address.line' => ['required_with:postal_address', 'string', 'max:255'],
            'postal_address.postal_code' => ['required_with:postal_address', 'string', 'max:16'],
            'postal_address.city' => ['required_with:postal_address', 'string', 'max:120'],
            'postal_address.country' => ['required_with:postal_address', 'string', 'regex:/^[A-Za-z]{2}$/'],
        ];
    }

    /**
     * @param  array{line: string, postal_code: string, city: string, country: string}  $address
     * @return array{line: string, postal_code: string, city: string, country: string}
     */
    public static function normalize(array $address): array
    {
        return [
            'line' => trim($address['line']),
            'postal_code' => strtoupper(trim($address['postal_code'])),
            'city' => trim($address['city']),
            'country' => strtoupper($address['country']),
        ];
    }

    /**
     * @param  array<string, string>|null  $address
     */
    public static function format(?array $address): ?string
    {
        if (! $address) {
            return null;
        }

        return "{$address['line']}, {$address['postal_code']} {$address['city']}, {$address['country']}";
    }
}
