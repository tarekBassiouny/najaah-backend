<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Instructor;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use App\Services\Storage\Contracts\StorageServiceInterface;
use App\Services\Storage\StoragePathResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class InstructorService implements InstructorServiceInterface
{
    use NormalizesTranslations;

    private const TRANSLATION_FIELDS = [
        'name_translations',
        'bio_translations',
        'title_translations',
    ];

    public function __construct(
        private readonly StorageServiceInterface $storageService,
        private readonly StoragePathResolver $pathResolver
    ) {}

    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Instructor::query()
            ->with(['center', 'creator'])
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Instructor
    {
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS);
        $data = $this->prepareAvatar($data);
        $data = $this->prepareMetadata($data);

        return Instructor::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Instructor $instructor, array $data): Instructor
    {
        $data = $this->normalizeTranslations($data, self::TRANSLATION_FIELDS, [
            'name_translations' => $instructor->name_translations ?? [],
            'bio_translations' => $instructor->bio_translations ?? [],
            'title_translations' => $instructor->title_translations ?? [],
        ]);
        $data = $this->prepareAvatar($data, $instructor);
        $data = $this->prepareMetadata($data);

        $instructor->update($data);

        return $instructor->fresh(['center', 'creator']) ?? $instructor;
    }

    public function delete(Instructor $instructor): void
    {
        $instructor->delete();
    }

    public function find(int $id): ?Instructor
    {
        return Instructor::with(['center', 'creator', 'courses'])->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareAvatar(array $data, ?Instructor $instructor = null): array
    {
        $avatar = $data['avatar'] ?? null;

        if ($avatar instanceof UploadedFile) {
            $centerId = $this->resolveCenterId($data, $instructor);
            $path = $this->pathResolver->instructorAvatar($centerId, $avatar->hashName());
            $storedPath = $this->storageService->upload($path, $avatar);

            $data['avatar_url'] = $storedPath;
        }

        unset($data['avatar']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareMetadata(array $data): array
    {
        $metadata = $data['metadata'] ?? null;
        unset($data['metadata']);

        if ($metadata === null) {
            return $data;
        }

        $allowed = config('instructors.metadata_keys', []);
        $clean = [];

        foreach ($metadata as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = array_values(array_filter($value, static fn ($item): bool => is_string($item)));

                continue;
            }

            if (is_string($value) || is_numeric($value)) {
                $clean[$key] = $value;
            }
        }

        $data['metadata'] = $clean;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCenterId(array $data, ?Instructor $instructor = null): int
    {
        $centerId = $data['center_id'] ?? null;

        if (is_numeric($centerId)) {
            return (int) $centerId;
        }

        if ($instructor instanceof Instructor && is_numeric($instructor->center_id)) {
            return (int) $instructor->center_id;
        }

        throw new RuntimeException('Center id is required to store instructor avatar.');
    }
}
