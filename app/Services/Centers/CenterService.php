<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Enums\VideoUploadStatus;
use App\Exceptions\NotFoundException;
use App\Filters\Admin\CenterFilters as AdminCenterFilters;
use App\Filters\Mobile\CenterFilters;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Centers\Contracts\CenterServiceInterface;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CenterService implements CenterServiceInterface
{
    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listAdmin(AdminCenterFilters $filters): LengthAwarePaginator
    {
        return $this->adminQuery($filters)->paginate($filters->perPage);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Center
    {
        return DB::transaction(function () use ($data): Center {
            $settings = $data['settings'] ?? null;
            unset($data['settings']);

            RejectNonScalarInput::validate($data, ['name', 'description']);
            if (array_key_exists('name', $data)) {
                $data['name_translations'] = $data['name'];
                unset($data['name']);
            }

            if (array_key_exists('description', $data)) {
                $data['description_translations'] = $data['description'];
                unset($data['description']);
            }

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

            RejectNonScalarInput::validate($data, ['name', 'description']);
            if (array_key_exists('name', $data)) {
                $data['name_translations'] = $data['name'];
                unset($data['name']);
            }

            if (array_key_exists('description', $data)) {
                $data['description_translations'] = $data['description'];
                unset($data['description']);
            }

            if (! empty($data)) {
                $center->update($data);
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

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listUnbranded(CenterFilters $filters): LengthAwarePaginator
    {
        $query = Center::query()
            ->with('setting')
            ->where('type', 0)
            ->orderByDesc('id');

        if ($filters->search !== null && $filters->search !== '') {
            $query->whereTranslationLike(
                ['name', 'description'],
                $filters->search,
                ['en', 'ar']
            );
        }

        if ($filters->isFeatured !== null) {
            $query->where('is_featured', $filters->isFeatured);
        }

        return $query->paginate($filters->perPage);
    }

    /**
     * @return Builder<Center>
     */
    private function adminQuery(AdminCenterFilters $filters): Builder
    {
        $query = Center::query()
            ->with('setting')
            // ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if ($filters->slug !== null) {
            $query->where('slug', $filters->slug);
        }

        if ($filters->type !== null) {
            $query->where('type', $filters->type);
        }

        if ($filters->tier !== null) {
            $query->where('tier', $filters->tier);
        }

        if ($filters->isFeatured !== null) {
            $query->where('is_featured', $filters->isFeatured);
        }

        if ($filters->onboardingStatus !== null) {
            $query->where('onboarding_status', $filters->onboardingStatus);
        }

        if ($filters->search !== null && $filters->search !== '') {
            // Search targets the stored base string; not locale-aware yet.
            $query->where('name_translations', 'like', '%'.$filters->search.'%');
        }

        if ($filters->createdFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($filters->createdFrom)->startOfDay());
        }

        if ($filters->createdTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($filters->createdTo)->endOfDay());
        }

        return $query;
    }

    /**
     * @return array{center: Center, courses: LengthAwarePaginator<Course>}
     */
    public function showWithCourses(User $student, Center $center, int $perPage = 15): array
    {
        $center->loadMissing('setting');

        $courses = $this->unbrandedCourseQuery($student, $center)
            ->paginate($perPage);

        return [
            'center' => $center,
            'courses' => $courses,
        ];
    }

    /**
     * @return Builder<Course>
     */
    private function unbrandedCourseQuery(User $student, Center $center): Builder
    {
        if ((int) $center->type !== 0) {
            $this->notFound();
        }

        return Course::query()
            ->where('center_id', $center->id)
            ->where('status', 3)
            ->where('is_published', true)
            ->with(['center', 'category', 'instructors'])
            ->withExists([
                'enrollments as is_enrolled' => function ($query) use ($student): void {
                    $query->where('user_id', $student->id)
                        ->where('status', Enrollment::STATUS_ACTIVE)
                        ->whereNull('deleted_at');
                },
            ])
            ->whereDoesntHave('videos', function ($query): void {
                $query->where('encoding_status', '!=', VideoUploadStatus::Ready->value)
                    ->orWhere('lifecycle_status', '!=', 2)
                    ->orWhere(function ($query): void {
                        $query->whereNotNull('upload_session_id')
                            ->whereHas('uploadSession', function ($query): void {
                                $query->where('upload_status', '!=', VideoUploadStatus::Ready->value);
                            });
                    });
            })
            ->orderByDesc('created_at');
    }

    private function notFound(): void
    {
        throw new NotFoundException('Center not found.', 404);
    }
}
