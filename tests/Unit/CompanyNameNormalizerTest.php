<?php

use App\Support\CompanyNameNormalizer;

dataset('names', [
    'cyrillic with legal suffix' => ['Нова Градба ДООЕЛ', 'nova gradba'],
    'latin doo suffix' => ['Nova Gradba DOO', 'nova gradba'],
    'dotted suffix and quotes' => ['"Нова Градба" Д.О.О.', 'nova gradba'],
    'prefix legal form' => ['АД Македонија Пат', 'makedonija pat'],
    'trailing mk' => ['Евро Дур МК', 'evro dur'],
    'special letters' => ['Ѓорѓи Џем Љубов', 'gjorgji dzhem ljubov'],
    'extra whitespace' => ['  Сара   4  ', 'sara 4'],
    'only legal token is kept' => ['ДОО', 'doo'],
]);

it('normalizes company names', function (string $input, string $expected) {
    expect((new CompanyNameNormalizer)->normalize($input))->toBe($expected);
})->with('names');

it('treats spelling and case variants as the same company', function () {
    $n = new CompanyNameNormalizer;

    expect($n->normalize('НОВА ГРАДБА дооел'))->toBe($n->normalize('Nova Gradba'));
});

it('builds a compact name for domains', function () {
    expect((new CompanyNameNormalizer)->compact('Нова Градба ДООЕЛ'))->toBe('novagradba');
});

it('handles empty input', function () {
    expect((new CompanyNameNormalizer)->normalize(null))->toBe('');
});

it('keeps letters that differ only by transliteration apart', function () {
    $n = new CompanyNameNormalizer;

    expect($n->normalize('Жана'))->not->toBe($n->normalize('Зана'))
        ->and($n->compact('Жана'))->toBe('zana');
});
