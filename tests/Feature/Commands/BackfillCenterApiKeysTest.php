<?php

declare(strict_types=1);

use App\Models\Center;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('commands', 'centers');

test('it backfills missing center api keys', function (): void {
    $withKey = Center::factory()->create(['api_key' => 'preset-center-key']);
    $missing = Center::factory()->count(2)->create();

    DB::table('centers')
        ->whereIn('id', $missing->pluck('id')->all())
        ->update(['api_key' => null]);

    $this->artisan('centers:backfill-api-keys')
        ->assertSuccessful()
        ->expectsOutputToContain('Backfilled 2 center API keys.');

    $filledKeys = Center::query()
        ->whereIn('id', $missing->pluck('id')->all())
        ->pluck('api_key')
        ->all();

    expect($filledKeys[0])->toBeString()->not->toBe('')
        ->and($filledKeys[1])->toBeString()->not->toBe('')
        ->and($filledKeys[0])->not->toBe($filledKeys[1]);

    expect($withKey->fresh()?->api_key)->toBe('preset-center-key');
});

test('it supports dry run for center api key backfill', function (): void {
    $missing = Center::factory()->create();

    DB::table('centers')
        ->where('id', $missing->id)
        ->update(['api_key' => null]);

    $this->artisan('centers:backfill-api-keys --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('Centers missing API keys: 1');

    expect(Center::query()->find($missing->id)?->api_key)->toBeNull();
});
