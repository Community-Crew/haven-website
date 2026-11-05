<?php

use App\Filament\Resources\FaqCategories\Pages\EditFaqCategory;
use App\Models\FaqCategory;
use App\Models\FaqQuestion;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

// The content migration fills these in - start each test empty instead.
beforeEach(function () {
    FaqCategory::query()->delete();
});

function faqCategory(array $attributes = []): FaqCategory
{
    return FaqCategory::create(['title' => 'Reserveringen', 'title_en' => 'Reservations', 'icon' => '🎟️', ...$attributes]);
}

function faqQuestion(FaqCategory $category, array $attributes = []): FaqQuestion
{
    return $category->questions()->create(['question' => 'Vraag?', 'answer' => 'Antwoord.', ...$attributes]);
}

it('starts with the seeded content after migrating', function () {
    // Re-run the content migration against the now-empty tables.
    (require database_path('migrations/2026_11_05_140100_seed_faq_content.php'))->up();

    expect(FaqCategory::count())->toBe(6)
        ->and(FaqQuestion::where('question_en', null)->count())->toBe(0);
});

it('returns categories and published questions in order, without logging in', function () {
    $second = faqCategory(['title' => 'Tweede', 'sort_order' => 2]);
    $first = faqCategory(['title' => 'Eerste', 'sort_order' => 1]);
    faqQuestion($first, ['question' => 'B?', 'sort_order' => 2]);
    faqQuestion($first, ['question' => 'A?', 'question_en' => 'A in English?', 'sort_order' => 1]);
    faqQuestion($second, ['question' => 'C?']);

    $this->getJson('/api/v1/faq')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Eerste')
        ->assertJsonPath('data.0.flowchart', null)
        ->assertJsonPath('data.0.questions.0.question', 'A?')
        ->assertJsonPath('data.0.questions.0.question_en', 'A in English?')
        ->assertJsonPath('data.0.questions.1.question', 'B?')
        ->assertJsonPath('data.0.questions.1.question_en', null)
        ->assertJsonPath('data.1.title', 'Tweede');
});

it('hides unpublished questions and categories that would be empty', function () {
    $hidden = faqCategory(['title' => 'Alleen concept']);
    faqQuestion($hidden, ['is_published' => false]);

    $flowOnly = faqCategory(['title' => 'Alleen schema', 'flowchart' => [['title' => 'Stap']]]);

    $this->getJson('/api/v1/faq')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $flowOnly->id);
});

it('returns answers as HTML with their links', function () {
    faqQuestion(faqCategory(), ['answer' => '<p>Ga naar <a href="/dashboard/rooms">Ruimtes</a>.</p>']);

    expect($this->getJson('/api/v1/faq')->json('data.0.questions.0.answer'))
        ->toContain('<a href="/dashboard/rooms">Ruimtes</a>')
        ->and($this->getJson('/api/v1/faq')->json('data.0.questions.0.answer_en'))->toBeNull();
});

it('returns a flowchart as plain lists, with nested decisions and only safe links', function () {
    // Filament's repeaters key items by UUID - the app wants lists.
    faqCategory(['flowchart' => [
        'uuid-a' => ['icon' => '📸', 'title' => 'Inchecken', 'tone' => 'good', 'url' => '/dashboard/reservations', 'branches' => []],
        'uuid-b' => ['title' => 'Op tijd?', 'url' => 'javascript:alert(1)', 'tone' => 'purple', 'branches' => [
            'uuid-c' => ['label' => 'Ja', 'steps' => ['uuid-d' => ['title' => 'Goed', 'url' => 'https://example.com']]],
            'uuid-e' => ['label' => 'Nee', 'steps' => ['uuid-f' => ['title' => 'Fout']]],
        ]],
    ]]);

    $flowchart = $this->getJson('/api/v1/faq')->json('data.0.flowchart');

    expect($flowchart)->toHaveCount(2)
        ->and($flowchart[0])->toMatchArray(['icon' => '📸', 'tone' => 'good', 'url' => '/dashboard/reservations', 'branches' => []])
        ->and($flowchart[1]['url'])->toBeNull()
        ->and($flowchart[1]['tone'])->toBe('default')
        ->and($flowchart[1]['branches'][0]['label'])->toBe('Ja')
        ->and($flowchart[1]['branches'][0]['steps'][0])->toMatchArray(['title' => 'Goed', 'url' => 'https://example.com'])
        ->and($flowchart[1]['branches'][1]['steps'][0]['title'])->toBe('Fout');
});

it('lets an admin save the seeded content unchanged through the editor', function () {
    (require database_path('migrations/2026_11_05_140100_seed_faq_content.php'))->up();
    $category = FaqCategory::where('title', 'In- en uitchecken')->sole();
    $before = $this->getJson('/api/v1/faq')->json('data.2');

    $admin = User::factory()->create(['email' => 'faq-roundtrip@example.com', 'keycloak_id' => 'kc-faq-roundtrip']);
    foreach (['ViewAny:FaqCategory', 'View:FaqCategory', 'Update:FaqCategory'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($admin);

    Livewire::test(EditFaqCategory::class, ['record' => $category->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->getJson('/api/v1/faq')->json('data.2'))->toEqual($before);
});

it('refuses a flowchart link that is not an app path or web address', function () {
    $category = faqCategory(['flowchart' => [['title' => 'Stap', 'tone' => 'default', 'url' => null, 'branches' => []]]]);

    $admin = User::factory()->create(['email' => 'faq-links@example.com', 'keycloak_id' => 'kc-faq-links']);
    foreach (['ViewAny:FaqCategory', 'View:FaqCategory', 'Update:FaqCategory'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($admin);

    $component = Livewire::test(EditFaqCategory::class, ['record' => $category->getRouteKey()]);
    $key = array_key_first($component->get('data.flowchart'));

    $component
        ->set("data.flowchart.{$key}.url", 'javascript:alert(1)')
        ->call('save')
        ->assertHasFormErrors(["flowchart.{$key}.url"]);
});

it('lets an admin edit a category and its questions together', function () {
    $category = faqCategory();
    faqQuestion($category, ['question' => 'Oud?']);

    $admin = User::factory()->create(['email' => 'faq-admin@example.com', 'keycloak_id' => 'kc-faq-admin']);
    foreach (['ViewAny:FaqCategory', 'View:FaqCategory', 'Update:FaqCategory'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($admin);

    $component = Livewire::test(EditFaqCategory::class, ['record' => $category->getRouteKey()]);
    $key = array_key_first($component->get('data.questions'));

    $component
        ->set("data.questions.{$key}.question", 'Nieuw?')
        ->set('data.title_en', 'Bookings')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->fresh()->title_en)->toBe('Bookings')
        ->and($category->questions()->sole()->question)->toBe('Nieuw?');
});

it('keeps admins without the permission out', function () {
    $user = User::factory()->create(['email' => 'not-faq-admin@example.com', 'keycloak_id' => 'kc-not-faq-admin']);

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($user);

    Livewire::test(EditFaqCategory::class, ['record' => faqCategory()->getRouteKey()])
        ->assertForbidden();
});
