<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\User;
use App\Services\Pdfs\Contracts\PdfAccessServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function __construct(private readonly PdfAccessServiceInterface $accessService) {}

    public function signedUrl(Request $request, Center $center, Course $course, Pdf $pdf): JsonResponse
    {
        $student = $request->user();

        if (! $student instanceof User || $student->is_student === false) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Only students can access PDFs.',
                ],
            ], 403);
        }

        if ((int) $course->center_id !== (int) $center->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Course not found.',
                ],
            ], 404);
        }

        $ttl = (int) config('pdf.signed_url_ttl', 600);
        $signed = $this->accessService->signedUrl($student, $course, (int) $pdf->id, $ttl);

        return response()->json([
            'success' => true,
            'data' => $signed,
        ]);
    }
}
