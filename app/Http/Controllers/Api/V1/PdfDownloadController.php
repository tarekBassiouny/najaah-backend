<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Pdf;
use App\Services\Pdfs\PdfAccessService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfDownloadController extends Controller
{
    public function __construct(private readonly PdfAccessService $accessService) {}

    public function __invoke(Request $request, Course $course, Pdf $pdf): StreamedResponse
    {
        /** @var \App\Models\User $student */
        $student = $request->user();

        return $this->accessService->download($student, $course, (int) $pdf->id);
    }
}
