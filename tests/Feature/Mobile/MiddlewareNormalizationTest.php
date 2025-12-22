<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('uses jwt.mobile for mobile routes', function (): void {
    $route = Route::getRoutes()->match(Request::create('/api/v1/courses/explore', 'GET'));
    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('jwt.mobile')
        ->and($middleware)->not->toContain('jwt.admin');
});
