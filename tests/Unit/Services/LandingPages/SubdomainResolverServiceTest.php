<?php

declare(strict_types=1);

use App\Models\Center;
use App\Services\LandingPages\SubdomainResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->group('landing-pages', 'unit');

beforeEach(function (): void {
    Config::set('app.base_domain', 'najaah.me');
    Config::set('app.url_scheme', 'https');
    $this->service = app(SubdomainResolverService::class);
});

it('resolves subdomain from host', function (): void {
    $subdomain = $this->service->resolveSubdomain('mycenter.najaah.me');

    expect($subdomain)->toBe('mycenter');
});

it('returns null for base domain', function (): void {
    $subdomain = $this->service->resolveSubdomain('najaah.me');

    expect($subdomain)->toBeNull();
});

it('returns null for www subdomain', function (): void {
    $subdomain = $this->service->resolveSubdomain('www.najaah.me');

    expect($subdomain)->toBeNull();
});

it('returns null for external domain', function (): void {
    $subdomain = $this->service->resolveSubdomain('example.com');

    expect($subdomain)->toBeNull();
});

it('identifies system domain correctly', function (): void {
    expect($this->service->isSystemDomain('najaah.me'))->toBeTrue()
        ->and($this->service->isSystemDomain('www.najaah.me'))->toBeTrue()
        ->and($this->service->isSystemDomain('center.najaah.me'))->toBeFalse();
});

it('gets center by subdomain', function (): void {
    $center = Center::factory()->create(['slug' => 'test-center']);

    $foundCenter = $this->service->getCenterBySubdomain('test-center');

    expect($foundCenter)->not->toBeNull()
        ->and($foundCenter->id)->toBe($center->id);
});

it('returns null for non-existent subdomain', function (): void {
    $foundCenter = $this->service->getCenterBySubdomain('non-existent');

    expect($foundCenter)->toBeNull();
});

it('builds correct center URL', function (): void {
    $center = Center::factory()->create(['slug' => 'test-center']);

    $url = $this->service->buildCenterUrl($center);

    expect($url)->toBe('https://test-center.najaah.me');
});

it('builds center URL with path', function (): void {
    $center = Center::factory()->create(['slug' => 'test-center']);

    $url = $this->service->buildCenterUrl($center, '/login');

    expect($url)->toBe('https://test-center.najaah.me/login');
});

it('handles path without leading slash', function (): void {
    $center = Center::factory()->create(['slug' => 'test-center']);

    $url = $this->service->buildCenterUrl($center, 'register');

    expect($url)->toBe('https://test-center.najaah.me/register');
});
