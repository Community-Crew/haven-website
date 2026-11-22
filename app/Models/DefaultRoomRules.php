<?php

namespace App\Models;

use App\Support\RoomRuleList;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row model (id 1): the rules that apply to every room, shown above a
 * room's own rules and accepted together with them.
 */
class DefaultRoomRules extends Model
{
    use Auditable;

    protected $table = 'default_room_rules';

    protected $fillable = [
        'rules',
    ];

    protected $casts = [
        'rules' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }

    /**
     * @return list<array{text: string, text_en: string|null}>
     */
    public function ruleList(): array
    {
        return RoomRuleList::normalize($this->rules);
    }
}
