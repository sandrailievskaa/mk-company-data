<?php

use App\Models\Company;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses Macedonian by default', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertOk()->assertSee('Контролна табла');
});

it('switches the language, remembers it and redirects back', function () {
    $this->actingAs(User::factory()->create());

    $this->from('/admin/companies')->get('/locale/en')
        ->assertRedirect('/admin/companies')
        ->assertSessionHas('locale', 'en')
        ->assertCookie('locale', 'en');

    $this->get('/admin/companies')->assertOk()->assertSee('Companies')->assertSee('Company name');
});

it('renders every panel page in English without leftover Macedonian UI text', function (string $url) {
    $company = Company::create(['name' => 'Nova Gradba', 'sector' => 'gradezhni-kompanii', 'city' => 'Skopje', 'email' => 'a@b.mk']);
    $offer = Offer::create(['title' => 'Title', 'content' => 'Body', 'company_id' => $company->id]);
    $this->actingAs(User::factory()->create());
    $this->withSession(['locale' => 'en']);

    $url = str_replace(['{company}', '{offer}'], [$company->id, $offer->id], $url);
    $html = $this->get($url)->assertOk()->getContent();

    // Visible text only: drop scripts, styles and tags, then look for Cyrillic.
    $text = preg_replace(['#<script\b.*?</script>#is', '#<style\b.*?</style>#is', '#<[^>]+>#'], ' ', $html);
    $text = html_entity_decode($text);
    preg_match_all('/[^\x00-\x7F]*[\x{0400}-\x{04FF}][^\s]*/u', $text, $m);

    // Company/offer data are user content; allow only the demo values (Latin) and the language label.
    $leftover = array_values(array_diff(array_unique($m[0]), ['МК', 'Македонски']));

    expect($leftover)->toBe([]);
})->with([
    'dashboard' => '/admin',
    'companies' => '/admin/companies',
    'company view' => '/admin/companies/{company}',
    'company edit' => '/admin/companies/{company}/edit',
    'offers' => '/admin/offers',
    'offer view' => '/admin/offers/{offer}',
    'offer create' => '/admin/offers/create',
    'scrape runs' => '/admin/scrape-runs',
    'pipeline' => '/admin/pipeline',
]);

it('rejects unknown languages', function () {
    $this->get('/locale/de')->assertNotFound();
});

it('has an English translation for every Macedonian key used in the code', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);
    $missing = [];

    foreach ([app_path(), resource_path('views')] as $root) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }
            preg_match_all("/(?:__|trans_choice)\(\s*'((?:[^'\\\\]|\\\\.)*)'/u", file_get_contents($file->getPathname()), $m);
            foreach ($m[1] as $raw) {
                $key = stripcslashes($raw);
                if (preg_match('/\p{Cyrillic}/u', $key) && ! isset($en[$key])) {
                    $missing[] = $key;
                }
            }
        }
    }

    expect($missing)->toBe([]);
});

it('formats plural strings in both languages', function () {
    expect(trans_choice('Вкупно :count процес на скрејпање/обработка за овој запис.|Вкупно :count процеси на скрејпање/обработка за овој запис.', 1))
        ->toContain('процес на')
        ->and(trans_choice('Вкупно :count процес на скрејпање/обработка за овој запис.|Вкупно :count процеси на скрејпање/обработка за овој запис.', 3))
        ->toContain('процеси');

    app()->setLocale('en');

    expect(trans_choice('Вкупно :count процес на скрејпање/обработка за овој запис.|Вкупно :count процеси на скрејпање/обработка за овој запис.', 1))
        ->toBe('Scraped/processed 1 time for this record in total.')
        ->and(trans_choice('Вкупно :count процес на скрејпање/обработка за овој запис.|Вкупно :count процеси на скрејпање/обработка за овој запис.', 3))
        ->toBe('Scraped/processed 3 times for this record in total.');
});
