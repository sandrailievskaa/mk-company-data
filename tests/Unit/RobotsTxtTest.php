<?php

use App\Support\RobotsTxt;

it('parses disallow rules and crawl delay for all agents', function () {
    $robots = new RobotsTxt("User-agent: *\nDisallow: /json/\nAllow: /\nCrawl-delay: 5\n");

    expect($robots->isAllowed('/json/x'))->toBeFalse()
        ->and($robots->isAllowed('/gradezhni-kompanii'))->toBeTrue()
        ->and($robots->isAllowed('/banki?filter_dejnost=1'))->toBeTrue()
        ->and($robots->crawlDelaySeconds())->toBe(5.0);
});

it('ignores rules of other agents', function () {
    $robots = new RobotsTxt("User-agent: BadBot\nDisallow: /\n\nUser-agent: *\nDisallow: /private\n");

    expect($robots->isAllowed('/public'))->toBeTrue()
        ->and($robots->isAllowed('/private/a'))->toBeFalse();
});

it('prefers the longest matching rule', function () {
    $robots = new RobotsTxt("User-agent: *\nDisallow: /a\nAllow: /a/open\n");

    expect($robots->isAllowed('/a/closed'))->toBeFalse()
        ->and($robots->isAllowed('/a/open/page'))->toBeTrue();
});

it('allows everything for an empty file', function () {
    expect((new RobotsTxt(''))->isAllowed('/anything'))->toBeTrue();
});
