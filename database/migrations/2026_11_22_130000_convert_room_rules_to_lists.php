<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rules become a list, so residents can tick each one separately:
 * [{"text": "Dutch", "text_en": "English or null"}, ...]. Any existing text
 * becomes a single rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->convert('rooms', 'rules', 'rules_en', 'rules');
        $this->convert('default_room_rules', 'content', 'content_en', 'rules');
        $this->convert('room_rule_acceptances', 'rules', 'rules_en', 'rules');
        $this->convert('room_rule_acceptances', 'default_rules', 'default_rules_en', 'default_rules');
    }

    public function down(): void
    {
        $this->revert('rooms', 'rules', 'rules', 'rules_en');
        $this->revert('default_room_rules', 'rules', 'content', 'content_en');
        $this->revert('room_rule_acceptances', 'rules', 'rules', 'rules_en');
        $this->revert('room_rule_acceptances', 'default_rules', 'default_rules', 'default_rules_en');
    }

    private function convert(string $table, string $dutch, string $english, string $target): void
    {
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->json('rules_list')->nullable());

        foreach (DB::table($table)->get() as $row) {
            $text = $this->plainText($row->{$dutch});

            if ($text !== '') {
                DB::table($table)->where('id', $row->id)->update([
                    'rules_list' => json_encode([[
                        'text' => $text,
                        'text_en' => $this->plainText($row->{$english}) ?: null,
                    ]]),
                ]);
            }
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn([$dutch, $english]));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('rules_list', $target));
    }

    private function revert(string $table, string $source, string $dutch, string $english): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->text('rules_text')->nullable();
            $blueprint->text('rules_text_en')->nullable();
        });

        foreach (DB::table($table)->get() as $row) {
            $rules = collect(json_decode((string) $row->{$source}, true) ?: []);

            if ($rules->isNotEmpty()) {
                DB::table($table)->where('id', $row->id)->update([
                    'rules_text' => $rules->map(fn ($rule) => '<p>'.e($rule['text']).'</p>')->implode(''),
                    'rules_text_en' => $rules->map(fn ($rule) => '<p>'.e($rule['text_en'] ?? $rule['text']).'</p>')->implode(''),
                ]);
            }
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($source));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('rules_text', $dutch));
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn('rules_text_en', $english));
    }

    private function plainText(?string $html): string
    {
        return trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));
    }
};
