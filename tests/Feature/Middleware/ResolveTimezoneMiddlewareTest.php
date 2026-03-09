<?php

declare(strict_types=1);

use App\Http\Middleware\ResolveTimezone;
use App\Models\Center;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Timezone\Contracts\TimezoneServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('timezone', 'middleware');

beforeEach(function (): void {
    $this->timezoneService = app(TimezoneServiceInterface::class);
    $this->middleware = new ResolveTimezone($this->timezoneService);
});

it('resolves timezone from query parameter', function (): void {
    $request = Request::create('/api/v1/test', 'GET', ['timezone' => 'Asia/Riyadh']);
    $timezone = null;

    $this->middleware->handle($request, function (Request $request) use (&$timezone) {
        $timezone = $request->attributes->get('timezone');

        return response('OK');
    });

    expect($timezone)->toBe('Asia/Riyadh');
});

it('ignores invalid timezone query parameter', function (): void {
    SystemSetting::query()->updateOrCreate([
        'key' => 'timezone',
    ], [
        'value' => ['timezone' => 'Africa/Cairo'],
        'is_public' => true,
    ]);

    $request = Request::create('/api/v1/test', 'GET', ['timezone' => 'Invalid/Timezone']);
    $timezone = null;

    $this->middleware->handle($request, function (Request $request) use (&$timezone) {
        $timezone = $request->attributes->get('timezone');

        return response('OK');
    });

    // Should fall back to system timezone
    expect($timezone)->toBe('Africa/Cairo');
});

it('resolves timezone from student center', function (): void {
    $center = Center::factory()->create(['timezone' => 'Asia/Dubai']);
    $student = User::factory()->create([
        'center_id' => $center->id,
        'is_student' => true,
    ]);

    $request = Request::create('/api/v1/test', 'GET');
    $request->setUserResolver(fn () => $student);

    $timezone = null;

    $this->middleware->handle($request, function (Request $request) use (&$timezone) {
        $timezone = $request->attributes->get('timezone');

        return response('OK');
    });

    expect($timezone)->toBe('Asia/Dubai');
});

it('falls back to system timezone when no context', function (): void {
    SystemSetting::query()->updateOrCreate([
        'key' => 'timezone',
    ], [
        'value' => ['timezone' => 'Africa/Cairo'],
        'is_public' => true,
    ]);

    $request = Request::create('/api/v1/test', 'GET');
    $timezone = null;

    $this->middleware->handle($request, function (Request $request) use (&$timezone) {
        $timezone = $request->attributes->get('timezone');

        return response('OK');
    });

    expect($timezone)->toBe('Africa/Cairo');
});

it('falls back to UTC when no system setting', function (): void {
    $request = Request::create('/api/v1/test', 'GET');
    $timezone = null;

    $this->middleware->handle($request, function (Request $request) use (&$timezone) {
        $timezone = $request->attributes->get('timezone');

        return response('OK');
    });

    expect($timezone)->toBe('UTC');
});
