<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    use Auditable;

    /**
     * Colours a flowchart step can have in the app.
     */
    public const STEP_TONES = [
        'default' => 'Neutral',
        'good' => 'Good (green)',
        'wait' => 'Waiting / warning (amber)',
        'bad' => 'Bad (red)',
    ];

    protected $fillable = [
        'title',
        'title_en',
        'icon',
        'flowchart',
        'sort_order',
    ];

    /**
     * flowchart: a list of steps, each
     * {icon, title, title_en, detail, detail_en, tone, url, branches}, where
     * branches is empty for a plain step, or the options of a decision:
     * [{label, label_en, steps: [...same shape...]}]. See FaqCategoryForm.
     */
    protected $casts = [
        'sort_order' => 'integer',
        'flowchart' => 'array',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(FaqQuestion::class)->orderBy('sort_order');
    }

    public function publishedQuestions(): HasMany
    {
        return $this->questions()->where('is_published', true);
    }
}
