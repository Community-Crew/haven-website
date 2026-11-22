<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * A version of the statutes or the house rules (huishoudelijk reglement).
 * Never edited: saving changed text adds a version, and a membership points
 * at the exact versions its member accepted.
 */
class MembershipDocument extends Model
{
    use Auditable;

    public const STATUTES = 'statutes';

    public const RULES = 'rules';

    protected $fillable = ['type', 'content', 'created_by'];

    public static function current(string $type): ?self
    {
        return static::query()->where('type', $type)->latest('id')->first();
    }

    public static function publish(string $type, string $content, ?User $by = null): self
    {
        $current = static::current($type);

        if ($current?->content === $content) {
            return $current;
        }

        return static::create(['type' => $type, 'content' => $content, 'created_by' => $by?->getKey()]);
    }
}
