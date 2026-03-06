<?php

declare(strict_types=1);

namespace App\Services\Education;

use App\Actions\Concerns\NormalizesTranslations;
use App\Exceptions\DomainException;
use App\Models\School;
use App\Models\User;
use App\Services\Centers\CenterScopeService;
use App\Services\Education\Contracts\SchoolServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SchoolService implements SchoolServiceInterface
{
    use NormalizesTranslations;

    public function __construct(
        private readonly CenterScopeService $centerScopeService
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<School>
     */
    public function paginateForCenter(User $admin, int $centerId, array $filters): LengthAwarePaginator
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $query = School::query()
            ->forCenter($centerId)
            ->withCount('students')
            ->orderBy('name_translations->en')
            ->orderBy('id');

        if (isset($filters['type']) && is_numeric($filters['type'])) {
            $query->where('type', (int) $filters['type']);
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

        $query = School::query()
            ->forCenter($centerId)
            ->orderBy('name_translations->en')
            ->orderBy('id');

        if ($activeOnly) {
            $query->active();
        }

        if (isset($filters['type']) && is_numeric($filters['type'])) {
            $query->where('type', (int) $filters['type']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL));
        }

        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $query->whereTranslationLike(['name'], trim($filters['search']), ['en', 'ar']);
        }

        /** @var Collection<int, School> $result */
        $result = $query->get();

        return $result;
    }

    public function createForCenter(User $admin, int $centerId, array $data): School
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);

        $payload = $this->normalizePayload($data, $centerId);

        return School::create($payload);
    }

    public function updateForCenter(User $admin, int $centerId, School $school, array $data): School
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        $this->assertCenterMatch($school, $centerId);

        $payload = $this->normalizePayload($data, $centerId, $school);

        $school->update($payload);

        return $school->refresh() ?? $school;
    }

    public function deleteForCenter(User $admin, int $centerId, School $school): void
    {
        $this->centerScopeService->assertAdminCenterId($admin, $centerId);
        $this->assertCenterMatch($school, $centerId);

        if ($school->students()->exists()) {
            throw new DomainException('Cannot delete school with assigned students.', 'SCHOOL_HAS_STUDENTS', 422);
        }

        $school->delete();
    }

    public function existsAndActive(int $schoolId, int $centerId): bool
    {
        return School::query()
            ->where('id', $schoolId)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->exists();
    }

    private function assertCenterMatch(School $school, int $centerId): void
    {
        if ((int) $school->center_id !== $centerId) {
            throw new DomainException('School not found.', 'SCHOOL_NOT_FOUND', 404);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data, int $centerId, ?School $school = null): array
    {
        $payload = $this->normalizeTranslations(
            $data,
            ['name_translations'],
            ['name_translations' => $school?->name_translations ?? []]
        );

        $nameTranslations = $payload['name_translations'] ?? [];
        $name = is_array($nameTranslations)
            ? ((string) ($nameTranslations['en'] ?? reset($nameTranslations) ?: 'school'))
            : 'school';
        $slugBase = (string) ($payload['slug'] ?? Str::slug($name));

        $payload['center_id'] = $centerId;
        $payload['slug'] = $this->uniqueSlug($centerId, $slugBase, $school?->id);
        $payload['is_active'] = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true;

        if (isset($payload['type'])) {
            $payload['type'] = (int) $payload['type'];
        }

        if (array_key_exists('address', $payload) && $payload['address'] !== null) {
            $payload['address'] = (string) $payload['address'];
        }

        return $payload;
    }

    private function uniqueSlug(int $centerId, string $slugBase, ?int $ignoreId = null): string
    {
        $base = trim($slugBase) !== '' ? Str::slug($slugBase) : 'school';
        $slug = $base;
        $counter = 2;

        while (School::query()
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
