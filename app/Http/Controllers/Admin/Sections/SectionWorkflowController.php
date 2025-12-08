<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sections;

use App\Actions\Sections\CreateSectionWithStructureAction;
use App\Actions\Sections\DeleteSectionWithStructureAction;
use App\Actions\Sections\PublishSectionAction;
use App\Actions\Sections\UnpublishSectionAction;
use App\Actions\Sections\UpdateSectionWithStructureAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sections\CreateSectionWithStructureRequest;
use App\Http\Requests\Sections\UpdateSectionWithStructureRequest;
use App\Http\Resources\Sections\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;

class SectionWorkflowController extends Controller
{
    public function createWithStructure(
        CreateSectionWithStructureRequest $request,
        CreateSectionWithStructureAction $createSectionWithStructureAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        /** @var array<int, int> $videos */
        $videos = is_array($data['videos'] ?? null) ? array_map('intval', $data['videos']) : [];
        /** @var array<int, int> $pdfs */
        $pdfs = is_array($data['pdfs'] ?? null) ? array_map('intval', $data['pdfs']) : [];
        $section = $createSectionWithStructureAction->execute(
            $data,
            $videos,
            $pdfs
        );

        return response()->json([
            'success' => true,
            'data' => new SectionResource($section->load(['videos', 'pdfs'])),
        ], 201);
    }

    public function updateWithStructure(
        UpdateSectionWithStructureRequest $request,
        Section $section,
        UpdateSectionWithStructureAction $updateSectionWithStructureAction
    ): JsonResponse {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        /** @var array<int, int> $videos */
        $videos = is_array($data['videos'] ?? null) ? array_map('intval', $data['videos']) : [];
        /** @var array<int, int> $pdfs */
        $pdfs = is_array($data['pdfs'] ?? null) ? array_map('intval', $data['pdfs']) : [];
        $updated = $updateSectionWithStructureAction->execute(
            $section,
            $data,
            $videos,
            $pdfs
        )->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($updated),
        ]);
    }

    public function deleteWithStructure(
        Section $section,
        DeleteSectionWithStructureAction $deleteSectionWithStructureAction
    ): JsonResponse {
        $deleteSectionWithStructureAction->execute($section);

        return response()->json([
            'success' => true,
            'data' => null,
        ], 204);
    }

    public function publish(
        Section $section,
        PublishSectionAction $publishSectionAction
    ): JsonResponse {
        $published = $publishSectionAction->execute($section)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($published),
        ]);
    }

    public function unpublish(
        Section $section,
        UnpublishSectionAction $unpublishSectionAction
    ): JsonResponse {
        $unpublished = $unpublishSectionAction->execute($section)->load(['videos', 'pdfs']);

        return response()->json([
            'success' => true,
            'data' => new SectionResource($unpublished),
        ]);
    }
}
