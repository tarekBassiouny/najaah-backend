<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function health(): JsonResponse
    {
        $checks = [
            'app' => true,
            'db' => true,
            'cache' => true,
        ];
        $status = 200;

        try {
            DB::select('select 1');
        } catch (\Throwable $throwable) {
            $checks['db'] = false;
            $status = 503;
        }

        try {
            Cache::put('health_check', 'ok', 5);
            $checks['cache'] = Cache::get('health_check') === 'ok';
            if (! $checks['cache']) {
                $status = 503;
            }
        } catch (\Throwable $throwable) {
            $checks['cache'] = false;
            $status = 503;
        }

        return response()->json([
            'status' => $status === 200 ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $status);
    }

    public function up(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
        ]);
    }
}
