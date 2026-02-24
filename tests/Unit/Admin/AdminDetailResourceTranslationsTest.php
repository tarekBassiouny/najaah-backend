<?php

declare(strict_types=1);

use App\Http\Resources\Admin\PdfResource;
use App\Http\Resources\Admin\Sections\SectionResource;
use App\Http\Resources\Admin\Videos\VideoResource;
use App\Models\Pdf;
use App\Models\Section;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns localized and raw translations for section resource', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $section = Section::factory()->create([
        'title_translations' => [
            'en' => 'Section One',
            'ar' => 'القسم الأول',
        ],
        'description_translations' => [
            'en' => 'Section description',
            'ar' => 'وصف القسم',
        ],
    ]);

    $payload = (new SectionResource($section))
        ->toArray(Request::create('/', 'GET'));

    expect($payload['title'])->toBe('القسم الأول')
        ->and(data_get($payload, 'title_translations.en'))->toBe('Section One')
        ->and(data_get($payload, 'title_translations.ar'))->toBe('القسم الأول')
        ->and($payload['description'])->toBe('وصف القسم')
        ->and(data_get($payload, 'description_translations.en'))->toBe('Section description')
        ->and(data_get($payload, 'description_translations.ar'))->toBe('وصف القسم');
});

it('returns localized and raw translations for video resource', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $video = Video::factory()->create([
        'title_translations' => [
            'en' => 'Video One',
            'ar' => 'الفيديو الأول',
        ],
        'description_translations' => [
            'en' => 'Video description',
            'ar' => 'وصف الفيديو',
        ],
    ]);

    $payload = (new VideoResource($video))
        ->toArray(Request::create('/', 'GET'));

    expect($payload['title'])->toBe('الفيديو الأول')
        ->and(data_get($payload, 'title_translations.en'))->toBe('Video One')
        ->and(data_get($payload, 'title_translations.ar'))->toBe('الفيديو الأول')
        ->and($payload['description'])->toBe('وصف الفيديو')
        ->and(data_get($payload, 'description_translations.en'))->toBe('Video description')
        ->and(data_get($payload, 'description_translations.ar'))->toBe('وصف الفيديو');
});

it('returns localized and raw translations for pdf resource', function (): void {
    config(['app.fallback_locale' => 'en']);
    app()->setLocale('ar');

    $pdf = Pdf::factory()->create([
        'title_translations' => [
            'en' => 'PDF One',
            'ar' => 'الملف الأول',
        ],
        'description_translations' => [
            'en' => 'PDF description',
            'ar' => 'وصف الملف',
        ],
    ]);

    $payload = (new PdfResource($pdf))
        ->toArray(Request::create('/', 'GET'));

    expect($payload['title'])->toBe('الملف الأول')
        ->and(data_get($payload, 'title_translations.en'))->toBe('PDF One')
        ->and(data_get($payload, 'title_translations.ar'))->toBe('الملف الأول')
        ->and($payload['description'])->toBe('وصف الملف')
        ->and(data_get($payload, 'description_translations.en'))->toBe('PDF description')
        ->and(data_get($payload, 'description_translations.ar'))->toBe('وصف الملف');
});
