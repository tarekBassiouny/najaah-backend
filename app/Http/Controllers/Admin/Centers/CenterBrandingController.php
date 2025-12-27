<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Centers;

use App\Actions\Admin\Centers\UploadCenterLogoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Centers\UploadCenterLogoRequest;
use App\Http\Resources\Admin\Centers\CenterResource;
use App\Models\Center;
use Illuminate\Http\JsonResponse;

class CenterBrandingController extends Controller
{
    public function uploadLogo(
        UploadCenterLogoRequest $request,
        int $center,
        UploadCenterLogoAction $action
    ): JsonResponse {
        $centerModel = Center::find($center);

        if ($centerModel === null) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Center not found',
                ],
            ], 404);
        }

        /** @var \Illuminate\Http\UploadedFile $logo */
        $logo = $request->file('logo');
        $updated = $action->execute($centerModel, $logo);

        return response()->json([
            'success' => true,
            'message' => 'Center logo updated successfully',
            'data' => new CenterResource($updated),
        ]);
    }
}
