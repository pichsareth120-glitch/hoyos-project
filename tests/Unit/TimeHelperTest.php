<?php

use App\Support\Time;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('time helper returns configured timezone', function () {
    expect(Time::timezone())->toBe('Asia/Phnom_Penh');
});

test('time helper formats date with ict label and utc tooltip', function () {
    $utcDate = Carbon::create(2026, 10, 8, 12, 0, 0, 'UTC');
    $html = (string) Time::format($utcDate, 'd M Y, H:i');

    // ICT is UTC+7, so 12:00 UTC is 19:00 ICT
    expect($html)->toContain('08 Oct 2026, 19:00 ICT')
        ->toContain('title="2026-10-08 12:00:00 UTC"')
        ->toContain('<time datetime="');
});

test('time helper handles null date safely', function () {
    expect((string) Time::format(null))->toBe('—');
    expect((string) Time::diffForHumans(null))->toBe('—');
});

test('time helper date format method omits ict label', function () {
    $utcDate = Carbon::create(2026, 10, 8, 12, 0, 0, 'UTC');
    $html = (string) Time::date($utcDate, 'd M Y');

    expect($html)->toContain('08 Oct 2026')
        ->not->toContain('ICT')
        ->toContain('title="2026-10-08 12:00:00 UTC"');
});

test('time helper diffForHumans includes detailed tooltip', function () {
    $date = Carbon::now()->subMinutes(5);
    $html = (string) Time::diffForHumans($date);

    expect($html)->toContain('title=')
        ->toContain('UTC')
        ->toContain('ICT')
        ->toContain('<time');
});
