<?php

declare(strict_types=1);

namespace App\Services\Videos;

use App\Models\User;
use App\Models\VideoUploadSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VideoUploadSessionQueryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<VideoUploadSession>
     */
    public function paginate(User $admin, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = VideoUploadSession::query()
            ->with(['videos'])
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $admin);
        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  Builder<VideoUploadSession>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<VideoUploadSession>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status']) && is_numeric($filters['status'])) {
            $query->where('upload_status', (int) $filters['status']);
        }

        if (isset($filters['center_id']) && is_numeric($filters['center_id'])) {
            $query->where('center_id', (int) $filters['center_id']);
        }

        return $query;
    }

    /**
     * @param  Builder<VideoUploadSession>  $query
     * @return Builder<VideoUploadSession>
     */
    private function applyScope(Builder $query, User $admin): Builder
    {
        if ($admin->center_id !== null) {
            $query->where('center_id', $admin->center_id);
        }

        return $query;
    }
}
