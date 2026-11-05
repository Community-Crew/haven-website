<?php

namespace App\Http\Controllers\Api\Faq;

use App\Filament\Resources\FaqCategories\Schemas\FaqCategoryForm;
use App\Http\Controllers\Controller;
use App\Models\FaqCategory;
use App\Models\FaqQuestion;
use Dedoc\Scramble\Attributes\Group;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Http\JsonResponse;

class FaqIndexController extends Controller
{
    /**
     * FAQ
     *
     * Every FAQ category with its published questions, in display order.
     * Dutch text plus an optional English translation (`*_en`, null when
     * not translated - fall back to the Dutch). Answers are HTML. Public,
     * like the privacy policy. A category with no published questions and
     * no flowchart is left out, since it would render empty.
     *
     * `flowchart` is null or a list of steps
     * `{icon, title, title_en, detail, detail_en, tone, url, branches}`;
     * `branches` is empty for a plain step, or a decision's options
     * `[{label, label_en, steps}]` with steps of the same shape.
     */
    #[Group('FAQ')]
    public function __invoke(): JsonResponse
    {
        $categories = FaqCategory::query()
            ->with('publishedQuestions')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (FaqCategory $category) => filled($category->flowchart) || $category->publishedQuestions->isNotEmpty())
            ->values();

        return response()->json([
            'data' => $categories->map(fn (FaqCategory $category) => [
                'id' => $category->id,
                'title' => $category->title,
                'title_en' => $category->title_en,
                'icon' => $category->icon,
                'flowchart' => filled($category->flowchart) ? $this->steps($category->flowchart) : null,
                'questions' => $category->publishedQuestions->map(fn (FaqQuestion $question) => [
                    'id' => $question->id,
                    'question' => $question->question,
                    'question_en' => $question->question_en,
                    'answer' => $this->html($question->answer),
                    'answer_en' => filled($question->answer_en) ? $this->html($question->answer_en) : null,
                ]),
            ]),
        ]);
    }

    protected function html(string $content): string
    {
        return RichContentRenderer::make($content)->toHtml();
    }

    /**
     * Normalizes what Filament's repeaters stored (keyed by item UUID) into
     * plain lists, with every field present. A link that doesn't fit
     * FaqCategoryForm::LINK_PATTERN is dropped - the form already refuses
     * those, this just keeps e.g. a javascript: URL out of the app no
     * matter how it got into the database.
     *
     * @param  array<array-key, array<string, mixed>>  $steps
     * @return array<int, array<string, mixed>>
     */
    protected function steps(array $steps): array
    {
        return collect($steps)->values()->map(fn (array $step) => [
            'icon' => $step['icon'] ?? null,
            'title' => $step['title'] ?? '',
            'title_en' => $step['title_en'] ?? null,
            'detail' => $step['detail'] ?? null,
            'detail_en' => $step['detail_en'] ?? null,
            'tone' => array_key_exists($step['tone'] ?? '', FaqCategory::STEP_TONES) ? $step['tone'] : 'default',
            'url' => is_string($step['url'] ?? null) && preg_match(FaqCategoryForm::LINK_PATTERN, $step['url']) ? $step['url'] : null,
            'branches' => collect($step['branches'] ?? [])->values()->map(fn (array $branch) => [
                'label' => $branch['label'] ?? '',
                'label_en' => $branch['label_en'] ?? null,
                'steps' => $this->steps($branch['steps'] ?? []),
            ])->all(),
        ])->all();
    }
}
