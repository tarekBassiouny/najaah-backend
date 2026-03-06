<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateVideoAccesses();

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->unique(['user_id', 'video_id', 'course_id'], 'video_accesses_user_video_course_unique');
        });

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->dropUnique('video_accesses_user_video_course_deleted_unique');
        });
    }

    public function down(): void
    {
        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->unique(
                ['user_id', 'video_id', 'course_id', 'deleted_at'],
                'video_accesses_user_video_course_deleted_unique'
            );
        });

        Schema::table('video_accesses', function (Blueprint $table): void {
            $table->dropUnique('video_accesses_user_video_course_unique');
        });
    }

    private function deduplicateVideoAccesses(): void
    {
        /** @var \Illuminate\Support\Collection<int, object{user_id:int,video_id:int,course_id:int}> $groups */
        $groups = DB::table('video_accesses')
            ->select(['user_id', 'video_id', 'course_id'])
            ->groupBy(['user_id', 'video_id', 'course_id'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            /** @var int|null $keepId */
            $keepId = DB::table('video_accesses')
                ->where('user_id', $group->user_id)
                ->where('video_id', $group->video_id)
                ->where('course_id', $group->course_id)
                ->orderByRaw('CASE WHEN revoked_at IS NULL AND deleted_at IS NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('id')
                ->value('id');

            if (! is_int($keepId)) {
                continue;
            }

            DB::table('video_accesses')
                ->where('user_id', $group->user_id)
                ->where('video_id', $group->video_id)
                ->where('course_id', $group->course_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }
};
