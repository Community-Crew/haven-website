<?php

namespace App\Support;

/**
 * Rules as stored: a list of ['text' => Dutch, 'text_en' => English or null].
 * Normalized because the admin's repeater keys items by UUID, and the hash of
 * the rules must only change when the rules themselves do.
 */
class RoomRuleList
{
    /**
     * @return list<array{text: string, text_en: string|null}>
     */
    public static function normalize(?array $rules): array
    {
        return collect($rules ?? [])
            ->map(fn (array $rule) => [
                'text' => trim((string) ($rule['text'] ?? '')),
                'text_en' => filled($rule['text_en'] ?? null) ? trim($rule['text_en']) : null,
            ])
            ->filter(fn (array $rule) => $rule['text'] !== '')
            ->values()
            ->all();
    }
}
