<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A record of one outbound email. Populated automatically by
 * App\Listeners\LogSentEmail for every Mailable the app sends - see that
 * class rather than writing to this table directly.
 */
class SentEmail extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'mailable',
        'to',
        'subject',
        'locale',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
