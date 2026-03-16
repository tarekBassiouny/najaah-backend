<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('uses jwt.mobile for authenticated mobile routes', function (): void {
    // Use an authenticated-only route (playback requires authentication)
    $route = Route::getRoutes()->match(Request::create('/api/v1/centers/1/courses/1/videos/1/request_playback', 'POST'));
    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('jwt.mobile')
        ->and($middleware)->not->toContain('jwt.admin');
});

it('uses jwt.mobile.optional for guest-browsable routes', function (): void {
    $route = Route::getRoutes()->match(Request::create('/api/v1/courses/explore', 'GET'));
    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('jwt.mobile.optional')
        ->and($middleware)->toContain('guest.browsing')
        ->and($middleware)->not->toContain('jwt.admin');
});
