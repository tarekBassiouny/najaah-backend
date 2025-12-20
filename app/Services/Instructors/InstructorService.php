<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Models\Instructor;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InstructorService implements InstructorServiceInterface
{
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
        $data = $this->prepareAvatar($data);
        $data = $this->prepareMetadata($data);

        return Instructor::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Instructor $instructor, array $data): Instructor
    {
        $data = $this->prepareAvatar($data);
        $data = $this->prepareMetadata($data, $instructor);

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
    private function prepareAvatar(array $data): array
    {
        $avatar = $data['avatar'] ?? null;

        if ($avatar instanceof UploadedFile) {
            $disk = config('filesystems.default', 'public');
            $path = Storage::disk($disk)->putFile('instructors/avatars', $avatar);
            if ($path === false) {
                throw new RuntimeException('Failed to store instructor avatar.');
            }

            $data['avatar_url'] = Storage::disk($disk)->url($path);
        }

        unset($data['avatar']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareMetadata(array $data, ?Instructor $instructor = null): array
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
}
