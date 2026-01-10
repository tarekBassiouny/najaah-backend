<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('uses jwt.admin for admin section routes', function (): void {
    $route = Route::getRoutes()->match(Request::create('/api/v1/admin/centers/1/courses/1/sections', 'GET'));
    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('jwt.admin')
        ->and($middleware)->not->toContain('auth:sanctum');
});
