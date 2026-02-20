<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $usedKeys = [];

        /** @var array<int, string> $existing */
        $existing = DB::table('centers')
            ->whereNotNull('api_key')
            ->where('api_key', '<>', '')
            ->pluck('api_key')
            ->all();

        foreach ($existing as $key) {
            $usedKeys[$key] = true;
        }

        $centers = DB::table('centers')
            ->select('id')
            ->where(function ($builder): void {
                $builder->whereNull('api_key')->orWhere('api_key', '');
            })
            ->orderBy('id')
            ->get();

        foreach ($centers as $center) {
            do {
                $key = bin2hex(random_bytes(20));
            } while (isset($usedKeys[$key]));

            $usedKeys[$key] = true;

            DB::table('centers')
                ->where('id', $center->id)
                ->update([
                    'api_key' => $key,
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty; API keys should not be removed once generated.
    }
};
