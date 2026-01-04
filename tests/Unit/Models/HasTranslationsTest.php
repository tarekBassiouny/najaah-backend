<?php

declare(strict_types=1);

use App\Models\Course;
use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns translated value when locale exists', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $course = Course::factory()->create([
        'title_translations' => ['en' => 'Base Title'],
    ]);

    Translation::create([
        'translatable_type' => Course::class,
        'translatable_id' => $course->id,
        'field' => 'title',
        'locale' => 'ar',
        'value' => 'Arabic Title',
    ]);

    expect($course->translate('title'))->toBe('Arabic Title');
});

it('falls back to base value when translation missing', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $course = Course::factory()->create([
        'title_translations' => ['en' => 'Base Title'],
    ]);

    expect($course->translate('title'))->toBe('Base Title');
});

it('normalizes translation locale on save', function (): void {
    $course = Course::factory()->create();

    $translation = Translation::create([
        'translatable_type' => Course::class,
        'translatable_id' => $course->id,
        'field' => 'title',
        'locale' => 'AR',
        'value' => 'Arabic',
    ]);

    expect($translation->locale)->toBe('ar');
});

it('rejects array translation values', function (): void {
    $course = Course::factory()->create();

    expect(function () use ($course): void {
        Translation::create([
            'translatable_type' => Course::class,
            'translatable_id' => $course->id,
            'field' => 'title',
            'locale' => 'en',
            'value' => ['en' => 'Bad'],
        ]);
    })->toThrow(InvalidArgumentException::class);
});
