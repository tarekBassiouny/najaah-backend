<?php

declare(strict_types=1);

namespace App\Services\Education;

use App\Actions\Concerns\NormalizesTranslations;
use App\Exceptions\DomainException;
use App\Models\Grade;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Education\Contracts\GradeServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GradeService implements GradeServiceInterface
{
    use NormalizesTranslations;

    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Grade>
     */
    public function paginateForCenter(User $admin, int $centerId, array $filters): LengthAwarePaginator
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = Grade::query()
            ->forCenter($centerId)
            ->withCount('students')
            ->orderBy('order')
            ->orderBy('id');

        if (isset($filters['stage']) && is_numeric($filters['stage'])) {
            $query->where('stage', (int) $filters['stage']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL));
        }

        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $query->whereTranslationLike(['name'], trim($filters['search']), ['en', 'ar']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function lookupForCenter(User $admin, int $centerId, array $filters, bool $activeOnly = false): Collection
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = Grade::query()
            ->forCenter($centerId)
            ->orderBy('order')
            ->orderBy('id');

        if ($activeOnly) {
            $query->active();
        }

        if (isset($filters['stage']) && is_numeric($filters['stage'])) {
            $query->where('stage', (int) $filters['stage']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL));
        }

        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $query->whereTranslationLike(['name'], trim($filters['search']), ['en', 'ar']);
        }

        /** @var Collection<int, Grade> $result */
        $result = $query->get();

        return $result;
    }

    public function createForCenter(User $admin, int $centerId, array $data): Grade
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $payload = $this->normalizePayload($data, $centerId);

        return Grade::create($payload);
    }

    public function updateForCenter(User $admin, int $centerId, Grade $grade, array $data): Grade
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        $this->assertCenterMatch($grade, $centerId);

        $payload = $this->normalizePayload($data, $centerId, $grade);

        $grade->update($payload);

        return $grade->refresh() ?? $grade;
    }

    public function deleteForCenter(User $admin, int $centerId, Grade $grade): void
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        $this->assertCenterMatch($grade, $centerId);

        if ($grade->students()->exists()) {
            throw new DomainException('Cannot delete grade with assigned students.', 'GRADE_HAS_STUDENTS', 422);
        }

        $grade->delete();
    }

    public function existsAndActive(int $gradeId, int $centerId): bool
    {
        return Grade::query()
            ->where('id', $gradeId)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->exists();
    }

    private function assertCenterMatch(Grade $grade, int $centerId): void
    {
        if ((int) $grade->center_id !== $centerId) {
            throw new DomainException('Grade not found.', 'GRADE_NOT_FOUND', 404);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, int $centerId, ?Grade $grade = null): array
    {
        $payload = $this->normalizeTranslations(
            $data,
            ['name_translations'],
            ['name_translations' => $grade?->name_translations ?? []]
        );

        $nameTranslations = $payload['name_translations'] ?? [];
        $name = is_array($nameTranslations)
            ? ((string) ($nameTranslations['en'] ?? reset($nameTranslations) ?: 'grade'))
            : 'grade';
        $slugBase = (string) ($payload['slug'] ?? Str::slug($name));

        $payload['center_id'] = $centerId;
        $payload['slug'] = $this->uniqueSlug($centerId, $slugBase, $grade?->id);
        $payload['is_active'] = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true;

        if (isset($payload['stage'])) {
            $payload['stage'] = (int) $payload['stage'];
        }

        if (isset($payload['order'])) {
            $payload['order'] = (int) $payload['order'];
        }

        return $payload;
    }

    private function uniqueSlug(int $centerId, string $slugBase, ?int $ignoreId = null): string
    {
        $base = trim($slugBase) !== '' ? Str::slug($slugBase) : 'grade';
        $slug = $base;
        $counter = 2;

        while (Grade::query()
            ->where('center_id', $centerId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, static fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
