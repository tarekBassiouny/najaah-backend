<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Playback\AuthorizePlaybackRequest;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Playback\PlaybackAuthorizationService;
use Illuminate\Http\JsonResponse;

class PlaybackController extends Controller
{
    public function __construct(
        private readonly PlaybackAuthorizationService $authorizationService
    ) {}

    public function authorize(
        AuthorizePlaybackRequest $request,
        Course $course,
        Video $video
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required.',
                ],
            ], 403);
        }

        $sectionId = $request->integer('section_id');
        $section = $sectionId !== 0 ? Section::find($sectionId) : null;

        $result = $this->authorizationService->authorize(
            $user,
            $course,
            $video,
            $section,
            (string) $request->input('device_id')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'embed_config' => $result['embed_config'],
            ],
        ]);
    }
}
