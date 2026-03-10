<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile\Education;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Education\ListMobileEducationRequest;
use App\Http\Resources\Mobile\Education\CollegeResource;
use App\Http\Resources\Mobile\Education\GradeResource;
use App\Http\Resources\Mobile\Education\SchoolResource;
use App\Models\Center;
use App\Models\College;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class EducationLookupController extends Controller
{
    public function index(ListMobileEducationRequest $request, Center $center): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access education.',
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
        $resolvedSettings = array_merge([
            'enable_grade' => true,
            'enable_school' => true,
            'enable_college' => true,
            'require_grade' => false,
            'require_school' => false,
            'require_college' => false,
        ], $profile);

        $grades = (bool) ($resolvedSettings['enable_grade'] ?? true)
            ? GradeResource::collection(
                Grade::query()
                    ->where('center_id', (int) $center->id)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->orderBy('id')
                    ->get()
            )->resolve()
            : [];

        $schools = (bool) ($resolvedSettings['enable_school'] ?? true)
            ? SchoolResource::collection(
                School::query()
                    ->where('center_id', (int) $center->id)
                    ->where('is_active', true)
                    ->orderBy('name_translations->en')
                    ->orderBy('id')
                    ->get()
            )->resolve()
            : [];

        $colleges = (bool) ($resolvedSettings['enable_college'] ?? true)
            ? CollegeResource::collection(
                College::query()
                    ->where('center_id', (int) $center->id)
                    ->where('is_active', true)
                    ->orderBy('name_translations->en')
                    ->orderBy('id')
                    ->get()
            )->resolve()
            : [];

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => [
                    'enable_grade' => (bool) ($resolvedSettings['enable_grade'] ?? true),
                    'enable_school' => (bool) ($resolvedSettings['enable_school'] ?? true),
                    'enable_college' => (bool) ($resolvedSettings['enable_college'] ?? true),
                    'require_grade' => (bool) ($resolvedSettings['require_grade'] ?? false),
                    'require_school' => (bool) ($resolvedSettings['require_school'] ?? false),
                    'require_college' => (bool) ($resolvedSettings['require_college'] ?? false),
                ],
                'lookups' => [
                    'grades' => $grades,
                    'schools' => $schools,
                    'colleges' => $colleges,
                ],
            ],
        ]);
    }
}
