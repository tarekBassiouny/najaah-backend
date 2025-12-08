<?php

declare(strict_types=1);

namespace App\Services\Instructors;

use App\Models\Instructor;
use App\Services\Instructors\Contracts\InstructorServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        return Instructor::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Instructor $instructor, array $data): Instructor
    {
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
}
