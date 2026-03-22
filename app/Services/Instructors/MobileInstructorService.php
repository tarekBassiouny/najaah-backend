<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Filters\Mobile\InstructorFilters;
use App\Models\Center;
use App\Models\Instructor;
use App\Models\User;
use App\Services\Instructors\Contracts\MobileInstructorServiceInterface;
use App\Services\Settings\PolicySettingsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MobileInstructorService implements MobileInstructorServiceInterface
{
    public function __construct(
        private readonly PolicySettingsService $policySettingsService
    ) {}

    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function list(?User $student, InstructorFilters $filters): LengthAwarePaginator
    {
        $query = Instructor::query()
            ->orderByDesc('created_at');

        if ($student instanceof User) {
            $query->visibleToStudent($student);
        } else {
            // Guest user - show instructors from centers that allow guest browsing
            $policySettingsService = $this->policySettingsService;

            $query->whereHas('center',
                /** @param Builder<Center> $query */
                function (Builder $query) use ($policySettingsService): void {
                    $query->where('status', Center::STATUS_ACTIVE->value);
                    $policySettingsService->applyGuestBrowsingFilter($query);
                }
            );
        }

        if ($filters->search !== null) {
            $query->whereTranslationLike(
                ['name', 'title'],
                $filters->search,
                ['en', 'ar']
            );
        }

        return $query->paginate(
            $filters->perPage,
            ['*'],
            'page',
            $filters->page
        );
    }
}
