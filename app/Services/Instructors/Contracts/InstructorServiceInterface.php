<?php

declare(strict_types=1);

namespace App\Services\Instructors\Contracts;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InstructorServiceInterface
{
    /**
     * @return LengthAwarePaginator<Instructor>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Instructor;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Instructor $instructor, array $data, ?User $actor = null): Instructor;

    public function delete(Instructor $instructor, ?User $actor = null): void;

    public function find(int $id): ?Instructor;
}
