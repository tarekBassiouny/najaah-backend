<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Models\Center;
use App\Models\CenterSetting;
use App\Services\Centers\Contracts\CenterServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CenterService implements CenterServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Center>
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Center::query()->with('setting')->orderByDesc('id');

        if (isset($filters['slug']) && is_string($filters['slug'])) {
            $query->where('slug', $filters['slug']);
        }

        if (isset($filters['type']) && is_numeric($filters['type'])) {
            $query->where('type', (int) $filters['type']);
        }

        if (isset($filters['search']) && is_string($filters['search'])) {
            $term = trim($filters['search']);
            if ($term !== '') {
                $query->where('name_translations', 'like', '%'.$term.'%');
            }
        }

        return $query->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Center
    {
        return DB::transaction(function () use ($data): Center {
            $settings = $data['settings'] ?? null;
            unset($data['settings']);

            /** @var Center $center */
            $center = Center::create($data);

            if (is_array($settings)) {
                CenterSetting::create([
                    'center_id' => $center->id,
                    'settings' => $settings,
                ]);
            }

            return $center->fresh(['setting']) ?? $center;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Center $center, array $data): Center
    {
        return DB::transaction(function () use ($center, $data): Center {
            $settings = $data['settings'] ?? null;
            unset($data['settings'], $data['slug']);

            if (! empty($data)) {
                $center->update($data);
                $center->save();
            }

            if (is_array($settings)) {
                $center->setting()
                    ->updateOrCreate(['center_id' => $center->id], ['settings' => $settings]);
            }

            $center->refresh();

            return $center->load('setting');
        });
    }

    public function delete(Center $center): void
    {
        $center->delete();
    }

    public function restore(int $id): ?Center
    {
        /** @var Center|null $center */
        $center = Center::withTrashed()->find($id);

        if ($center === null) {
            return null;
        }

        $center->restore();

        return $center->fresh(['setting']) ?? $center;
    }
}
