<?php

namespace App\Providers;

use App\Services\AdminAuthService;
use App\Services\Contracts\AdminAuthServiceInterface;
use App\Services\Contracts\DeviceServiceInterface;
use App\Services\Contracts\JwtServiceInterface;
use App\Services\Contracts\OtpServiceInterface;
use App\Services\DeviceService;
use App\Services\JwtService;
use App\Services\OtpService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $bindings = [
            OtpServiceInterface::class => OtpService::class,
            JwtServiceInterface::class => JwtService::class,
            DeviceServiceInterface::class => DeviceService::class,
            AdminAuthServiceInterface::class => AdminAuthService::class,
        ];

        foreach ($bindings as $abstract => $implementation) {
            $this->app->bind($abstract, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
