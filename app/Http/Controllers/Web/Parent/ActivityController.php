<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Parent;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Parents\Contracts\ParentProgressServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ParentProgressServiceInterface $progressService
    ) {}

    public function index(Request $request, int $student, int $center): JsonResponse
    {
        /** @var User $parent */
        $parent = $request->user();

        $days = (int) ($request->query('days', '7'));
        $timezone = (string) $request->attributes->get('timezone', (string) config('app.timezone', 'UTC'));

        $activity = $this->progressService->getWeeklyActivity($parent, $student, $center, $days, $timezone);

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }
}
