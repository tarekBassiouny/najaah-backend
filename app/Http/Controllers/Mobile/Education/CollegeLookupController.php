<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Education\ListMobileCollegesRequest;
use App\Http\Resources\Mobile\Education\CollegeResource;
use App\Models\Center;
use App\Models\College;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CollegeLookupController extends Controller
{
    public function index(ListMobileCollegesRequest $request, Center $center): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access colleges.',
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

        if ((bool) ($profile['enable_college'] ?? true) === false) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $validated = $request->validated();
        $query = College::query()
            ->where('center_id', (int) $center->id)
            ->where('is_active', true)
            ->orderBy('name_translations->en')
            ->orderBy('id');

        if (isset($validated['search']) && is_string($validated['search']) && trim($validated['search']) !== '') {
            $query->whereTranslationLike(['name'], trim($validated['search']), ['en', 'ar']);
        }

        return response()->json([
            'success' => true,
            'data' => CollegeResource::collection($query->get()),
        ]);
    }
}
