<?php

declare(strict_types=1);

namespace App\Services\Instructors\Contracts;

use App\Models\Instructor;
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
    public function create(array $data): Instructor;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Instructor $instructor, array $data): Instructor;

    public function delete(Instructor $instructor): void;

    public function find(int $id): ?Instructor;
}
