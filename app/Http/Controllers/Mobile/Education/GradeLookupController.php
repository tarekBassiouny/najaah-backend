<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Education\ListMobileGradesRequest;
use App\Http\Resources\Mobile\Education\GradeResource;
use App\Models\Center;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class GradeLookupController extends Controller
{
    public function index(ListMobileGradesRequest $request, Center $center): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access grades.',
                ],
            ], 403);
        }

        if (! $student->belongsToCenter((int) $center->id)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CENTER_MISMATCH',
                    'message' => 'Student does not belong to this center.',
                ],
            ], 403);
        }

        $settings = is_array($center->setting?->settings ?? null) ? $center->setting->settings : [];
        $profile = is_array($settings['education_profile'] ?? null) ? $settings['education_profile'] : [];

        if ((bool) ($profile['enable_grade'] ?? true) === false) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $validated = $request->validated();
        $query = Grade::query()
            ->where('center_id', (int) $center->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id');

        if (isset($validated['stage']) && is_numeric($validated['stage'])) {
            $query->where('stage', (int) $validated['stage']);
        }

        if (isset($validated['search']) && is_string($validated['search']) && trim($validated['search']) !== '') {
            $query->whereTranslationLike(['name'], trim($validated['search']), ['en', 'ar']);
        }

        return response()->json([
            'success' => true,
            'data' => GradeResource::collection($query->get()),
        ]);
    }
}
