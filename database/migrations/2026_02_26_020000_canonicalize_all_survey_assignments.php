<?php

declare(strict_types=1);

use App\Enums\SurveyAssignableType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        /** @var array<int, int> $surveyIds */
        $surveyIds = DB::table('survey_assignments')
            ->where('assignable_type', SurveyAssignableType::All->value)
            ->whereNull('deleted_at')
            ->pluck('survey_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        foreach ($surveyIds as $surveyId) {
            // Keep at most one canonical "all" row (assignable_id = 0).
            $canonicalRows = DB::table('survey_assignments')
                ->where('survey_id', $surveyId)
                ->where('assignable_type', SurveyAssignableType::All->value)
                ->where('assignable_id', 0)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if ($canonicalRows === []) {
                DB::table('survey_assignments')->insert([
                    'survey_id' => $surveyId,
                    'assignable_type' => SurveyAssignableType::All->value,
                    'assignable_id' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);
            } elseif (count($canonicalRows) > 1) {
                $duplicateIds = array_slice($canonicalRows, 1);
                DB::table('survey_assignments')
                    ->whereIn('id', $duplicateIds)
                    ->update([
                        'deleted_at' => $now,
                        'updated_at' => $now,
                    ]);
            }

            // Soft-delete all non-canonical active "all" rows.
            DB::table('survey_assignments')
                ->where('survey_id', $surveyId)
                ->where('assignable_type', SurveyAssignableType::All->value)
                ->where('assignable_id', '!=', 0)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => $now,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Data normalization migration; intentionally no rollback.
    }
};
