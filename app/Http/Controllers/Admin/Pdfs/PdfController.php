<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Pdfs;

use App\Http\Controllers\Concerns\AdminAuthenticates;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pdfs\ListPdfsRequest;
use App\Http\Requests\Admin\Pdfs\StorePdfRequest;
use App\Http\Requests\Admin\Pdfs\UpdatePdfRequest;
use App\Http\Resources\Admin\PdfResource;
use App\Models\Center;
use App\Models\Pdf;
use App\Services\Pdfs\Contracts\AdminPdfQueryServiceInterface;
use App\Services\Pdfs\Contracts\PdfServiceInterface;
use Illuminate\Http\JsonResponse;

class PdfController extends Controller
{
    use AdminAuthenticates;

    public function __construct(
        private readonly PdfServiceInterface $pdfService,
        private readonly AdminPdfQueryServiceInterface $queryService
    ) {}

    /**
     * List PDFs.
     */
    public function index(ListPdfsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $filters = $request->filters();

        $paginator = $this->queryService->paginateForCenter($admin, $center, $filters);

        return response()->json([
            'success' => true,
            'data' => PdfResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Create a PDF.
     */
    public function store(StorePdfRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $pdf = $this->pdfService->create($center, $admin, $data);

        return response()->json([
            'success' => true,
            'data' => new PdfResource($pdf),
        ], 201);
    }

    /**
     * Show a PDF.
     */
    public function show(Center $center, Pdf $pdf): JsonResponse
    {
        $this->requireAdmin();
        $this->assertPdfBelongsToCenter($center, $pdf);

        return response()->json([
            'success' => true,
            'data' => new PdfResource($pdf),
        ]);
    }

    /**
     * Update a PDF.
     */
    public function update(UpdatePdfRequest $request, Center $center, Pdf $pdf): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertPdfBelongsToCenter($center, $pdf);
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        $updated = $this->pdfService->update($pdf, $admin, $data);

        return response()->json([
            'success' => true,
            'data' => new PdfResource($updated),
        ]);
    }

    /**
     * Delete a PDF.
     */
    public function destroy(Center $center, Pdf $pdf): JsonResponse
    {
        $admin = $this->requireAdmin();
        $this->assertPdfBelongsToCenter($center, $pdf);
        $this->pdfService->delete($pdf, $admin);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    private function assertPdfBelongsToCenter(Center $center, Pdf $pdf): void
    {
        if ((int) $pdf->center_id !== (int) $center->id) {
            $this->notFound('PDF not found.');
        }
    }
}
