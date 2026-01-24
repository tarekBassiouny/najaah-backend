<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('uploads:cleanup')
    ->withoutOverlapping()
    ->onOneServer()
    ->daily();
Schedule::command('playback:close-stale')
    ->withoutOverlapping()
    ->onOneServer()
    ->everyMinute();
