<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Pdfs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pdfs\ListPdfsRequest;
use App\Http\Requests\Admin\Pdfs\StorePdfRequest;
use App\Http\Requests\Admin\Pdfs\UpdatePdfRequest;
use App\Http\Resources\Admin\PdfResource;
use App\Models\Center;
use App\Models\Pdf;
use App\Models\User;
use App\Services\Pdfs\AdminPdfQueryService;
use App\Services\Pdfs\PdfService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class PdfController extends Controller
{
    public function __construct(
        private readonly PdfService $pdfService,
        private readonly AdminPdfQueryService $queryService
    ) {}

    public function index(ListPdfsRequest $request, Center $center): JsonResponse
    {
        $admin = $this->requireAdmin();

        $perPage = (int) $request->integer('per_page', 15);
        /** @var array<string, mixed> $filters */
        $filters = $request->validated();

        $paginator = $this->queryService->paginateForCenter($admin, $center, $perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => PdfResource::collection($paginator->items()),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

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

    public function show(Center $center, Pdf $pdf): JsonResponse
    {
        $this->requireAdmin();
        $this->assertPdfBelongsToCenter($center, $pdf);

        return response()->json([
            'success' => true,
            'data' => new PdfResource($pdf),
        ]);
    }

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
            $this->notFound();
        }
    }

    private function requireAdmin(): User
    {
        $admin = request()->user();

        if (! $admin instanceof User) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 401));
        }

        return $admin;
    }

    private function notFound(): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => [
                'code' => 'NOT_FOUND',
                'message' => 'PDF not found.',
            ],
        ], 404));
    }
}
