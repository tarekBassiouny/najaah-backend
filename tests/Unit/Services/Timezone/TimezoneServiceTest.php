<?php

declare(strict_types=1);

use App\Models\Center;
use App\Models\SystemSetting;
use App\Services\Timezone\TimezoneService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('timezone', 'unit');

beforeEach(function (): void {
    $this->service = new TimezoneService;
});

it('returns UTC as default system timezone when no setting exists', function (): void {
    $timezone = $this->service->getSystemTimezone();

    expect($timezone)->toBe('UTC');
});

it('returns system timezone from settings', function (): void {
    SystemSetting::query()->updateOrCreate([
        'key' => 'timezone',
    ], [
        'value' => ['timezone' => 'Africa/Cairo'],
        'is_public' => true,
    ]);

    $timezone = $this->service->getSystemTimezone();

    expect($timezone)->toBe('Africa/Cairo');
});

it('falls back to UTC for invalid system timezone', function (): void {
    SystemSetting::query()->updateOrCreate([
        'key' => 'timezone',
    ], [
        'value' => ['timezone' => 'Invalid/Timezone'],
        'is_public' => true,
    ]);

    $timezone = $this->service->getSystemTimezone();

    expect($timezone)->toBe('UTC');
});

it('returns center timezone', function (): void {
    $center = Center::factory()->create(['timezone' => 'Asia/Riyadh']);

    $timezone = $this->service->getCenterTimezone($center);

    expect($timezone)->toBe('Asia/Riyadh');
});

it('returns effective timezone from center when available', function (): void {
    $center = Center::factory()->create(['timezone' => 'Asia/Dubai']);

    $timezone = $this->service->resolveTimezone($center);

    expect($timezone)->toBe('Asia/Dubai');
});

it('returns system timezone when no center provided', function (): void {
    SystemSetting::query()->updateOrCreate([
        'key' => 'timezone',
    ], [
        'value' => ['timezone' => 'Africa/Cairo'],
        'is_public' => true,
    ]);

    $timezone = $this->service->resolveTimezone(null);

    expect($timezone)->toBe('Africa/Cairo');
});

it('formats datetime in ISO 8601 with timezone', function (): void {
    $date = Carbon::create(2026, 3, 9, 14, 30, 0, 'UTC');

    $formatted = $this->service->formatDateTime($date, 'Africa/Cairo');

    expect($formatted)->toBe('2026-03-09T16:30:00+02:00');
});

it('formats date without time', function (): void {
    $date = Carbon::create(2026, 3, 9, 23, 30, 0, 'UTC');

    // When converted to Cairo time (UTC+2), it becomes next day
    $formatted = $this->service->formatDate($date, 'Africa/Cairo');

    expect($formatted)->toBe('2026-03-10');
});

it('validates timezone correctly', function (): void {
    expect($this->service->isValidTimezone('Africa/Cairo'))->toBeTrue()
        ->and($this->service->isValidTimezone('Asia/Riyadh'))->toBeTrue()
        ->and($this->service->isValidTimezone('UTC'))->toBeTrue()
        ->and($this->service->isValidTimezone('Invalid/Timezone'))->toBeFalse()
        ->and($this->service->isValidTimezone('not-a-timezone'))->toBeFalse();
});

it('returns common timezones for Middle East', function (): void {
    $timezones = $this->service->getCommonTimezones();

    expect($timezones)->toBeArray()
        ->and($timezones)->toHaveKey('UTC')
        ->and($timezones)->toHaveKey('Africa/Cairo')
        ->and($timezones)->toHaveKey('Asia/Riyadh');
});
