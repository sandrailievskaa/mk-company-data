<?php

use App\Support\Fmt;

it('formats numbers per locale', function () {
    app()->setLocale('mk');
    expect(Fmt::num(6017))->toBe("6\u{00A0}017")
        ->and(Fmt::num(13.7, 1))->toBe('13,7')
        ->and(Fmt::num(0.8, 2, true))->toBe('0,8')
        ->and(Fmt::signed(-1.5))->toBe('−1,5');

    app()->setLocale('en');
    expect(Fmt::num(6017))->toBe('6,017')
        ->and(Fmt::num(13.7, 1))->toBe('13.7')
        ->and(Fmt::signed(2))->toBe('+2.0');
});
