<?php

use App\Ai\Agents\OfferAgent;
use App\Ai\Agents\RecommendationAgent;
use App\Ai\Agents\ValidationAgent;
use App\Enums\EmailSource;
use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Models\Company;
use App\Models\User;
use App\Services\Offers\OfferPromptBuilder;
use App\Support\OfferContentFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('builds a company profile prompt with sector, city and activity level', function () {
    $company = new Company([
        'name' => 'Нова Градба', 'sector' => 'gradezhni-kompanii', 'city' => 'Скопје', 'activity_index' => 0.7,
    ]);

    $prompt = app(OfferPromptBuilder::class)->build($company, 'Софтвер за залихи');

    expect($prompt)->toContain('Нова Градба')
        ->toContain('Градежен')
        ->toContain('Скопје')
        ->toContain('Activity level: high')
        ->toContain('Софтвер за залихи');
});

it('formats plain-text offers into paragraphs', function () {
    $html = app(OfferContentFormatter::class)->toHtml("Прв <пасус>.\n\nВтор пасус.");

    expect($html)->toContain('<p>Прв &lt;пасус&gt;.</p>')->toContain('<p>Втор пасус.</p>');
});

it('keeps safe formatting from the rich editor and removes scripts and attributes', function () {
    $html = app(OfferContentFormatter::class)->toHtml(
        '<p onclick="x()">Здраво <strong>свет</strong></p><script>alert(1)</script><a href="javascript:evil()">a</a><a href="https://firma.mk" target="_blank">b</a>'
    );

    expect($html)->toContain('<p>Здраво <strong>свет</strong></p>')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->toContain('<a href="https://firma.mk">b</a>');
});

it('generates an offer with the company context and fills the form', function () {
    $this->actingAs(User::factory()->create());
    $company = Company::create(['name' => 'Нова Градба', 'sector' => 'gradezhni-kompanii', 'city' => 'Скопје']);

    OfferAgent::fake([['title' => 'Предлог за соработка', 'content' => "Прв.\n\nВтор."]]);

    Livewire::test(CreateOffer::class)
        ->fillForm(['company_id' => $company->id, 'additional_information' => 'Софтвер за залихи'])
        ->call('generateOfferContent')
        ->assertFormSet(['title' => 'Предлог за соработка']);

    OfferAgent::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Градежен') && str_contains($prompt->prompt, 'Софтвер за залихи'));
    expect(DB::table('ai_usage_logs')->where('source', 'offer')->count())->toBe(1);
});

it('recommends only unflagged companies from the selected sector and city', function () {
    $this->actingAs(User::factory()->create());

    $good = Company::create(['name' => 'Добра', 'sector' => 'programiranje', 'city' => 'Скопје', 'activity_index' => 0.9,
        'email' => 'office@dobra.mk', 'email_source' => EmailSource::Website]);
    Company::create(['name' => 'Дупликат', 'sector' => 'programiranje', 'city' => 'Скопје', 'activity_index' => 0.95, 'data_quality_flag' => 'duplicate']);
    Company::create(['name' => 'Друг град', 'sector' => 'programiranje', 'city' => 'Битола', 'activity_index' => 0.99]);

    RecommendationAgent::fake([['recommendations_json' => json_encode([
        ['company_id' => $good->id, 'reason' => 'Активна ИТ компанија.'],
        ['company_id' => 99999, 'reason' => 'Непостоечка.'],
    ])]]);

    $page = Livewire::test(CreateOffer::class)
        ->fillForm([
            'recommendation_title' => 'ERP', 'recommendation_sector' => 'programiranje', 'recommendation_city' => 'Скопје',
        ])
        ->call('generateCompanyRecommendations');

    expect($page->get('aiRecommendations'))->toHaveCount(1)
        ->and($page->get('aiRecommendations')[0]['company_id'])->toBe($good->id);

    RecommendationAgent::assertPrompted(fn ($p) => str_contains($p->prompt, 'Добра')
        && ! str_contains($p->prompt, 'Дупликат')
        && ! str_contains($p->prompt, 'Друг град'));
});

it('flags companies reported by the validation agent', function () {
    $a = Company::create(['name' => 'Дент арт', 'sector' => 'zdravstvo', 'city' => 'Скопје']);
    Company::create(['name' => 'Друга', 'sector' => 'zdravstvo', 'city' => 'Скопје']);

    ValidationAgent::fake([['issues_json' => json_encode([
        ['company_id' => $a->id, 'issue_type' => 'duplicate', 'reason' => 'Слично име', 'confidence' => 0.9],
        ['company_id' => 12345, 'issue_type' => 'inactive', 'reason' => 'Непозната', 'confidence' => 0.5],
    ])]]);

    $this->artisan('app:detect-invalid-companies')->assertSuccessful();

    expect($a->fresh()->data_quality_flag)->toBe('duplicate')
        ->and($a->fresh()->data_quality_note)->toContain('Слично име');
});
