<?php

declare(strict_types=1);

namespace App\Services\Centers;

use App\Enums\CenterType;
use App\Enums\VideoLifecycleStatus;
use App\Enums\VideoUploadStatus;
use App\Exceptions\NotFoundException;
use App\Filters\Admin\CenterFilters as AdminCenterFilters;
use App\Filters\Mobile\CenterFilters;
use App\Models\Center;
use App\Models\CenterSetting;
use App\Models\Course;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\Contracts\CenterServiceInterface;
use App\Support\AuditActions;
use App\Support\Guards\RejectNonScalarInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CenterService implements CenterServiceInterface
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listAdmin(AdminCenterFilters $filters): LengthAwarePaginator
    {
        return $this->adminQuery($filters)->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listAdminOptions(AdminCenterFilters $filters): LengthAwarePaginator
    {
        $query = Center::query()
            ->select(['id', 'slug', 'name_translations'])
            ->orderByDesc('created_at');

        $this->applyAdminFilters($query, $filters);

        return $query->paginate(
            $filters->perPage,
            ['id', 'slug', 'name_translations'],
            'page',
            $filters->page
        );
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $actor = null): Center
    {
        return DB::transaction(function () use ($data, $actor): Center {
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

            $fresh = $center->fresh(['setting']) ?? $center;

            $this->auditLogService->log($actor, $center, AuditActions::CENTER_CREATED);

            return $fresh;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Center $center, array $data, ?User $actor = null): Center
    {
        return DB::transaction(function () use ($center, $data, $actor): Center {
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

            $this->auditLogService->log($actor, $center, AuditActions::CENTER_UPDATED, [
                'updated_fields' => array_keys($data),
            ]);

            return $center->load('setting');
        });
    }

    public function delete(Center $center, ?User $actor = null): void
    {
        $center->delete();

        $this->auditLogService->log($actor, $center, AuditActions::CENTER_DELETED);
    }

    public function restore(int $id, ?User $actor = null): ?Center
    {
        /** @var Center|null $center */
        $center = Center::withTrashed()->find($id);

        if ($center === null) {
            return null;
        }

        $center->restore();

        $this->auditLogService->log($actor, $center, AuditActions::CENTER_RESTORED);

        return $center->fresh(['setting']) ?? $center;
    }

    /**
     * @return LengthAwarePaginator<Center>
     */
    public function listUnbranded(CenterFilters $filters): LengthAwarePaginator
    {
        $query = Center::query()
            ->with('setting')
            ->where('type', CenterType::Unbranded->value)
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

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
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

        $this->applyAdminFilters($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<Center>  $query
     */
    private function applyAdminFilters(Builder $query, AdminCenterFilters $filters): void
    {
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
            $term = $filters->search;

            $query->where(static function (Builder $builder) use ($term): void {
                $builder->whereTranslationLike(
                    ['name'],
                    $term,
                    ['en', 'ar']
                )->orWhere('slug', 'like', '%'.$term.'%');
            });
        }

        if ($filters->createdFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($filters->createdFrom)->startOfDay());
        }

        if ($filters->createdTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($filters->createdTo)->endOfDay());
        }
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
        if ($center->type !== CenterType::Unbranded) {
            $this->notFound();
        }

        return Course::query()
            ->where('center_id', $center->id)
            ->published()
            ->with(['center', 'category', 'instructors'])
            ->withEnrollmentMeta($student)
            ->whereDoesntHave('videos', function ($query): void {
                $query->where('encoding_status', '!=', VideoUploadStatus::Ready->value)
                    ->orWhere('lifecycle_status', '!=', VideoLifecycleStatus::Ready->value)
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
