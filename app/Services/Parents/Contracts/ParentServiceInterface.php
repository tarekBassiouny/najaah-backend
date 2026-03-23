<?php

declare(strict_types=1);

namespace App\Services\Parents\Contracts;

use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface ParentServiceInterface
{
    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function getLinkedStudents(User $parent, ?int $centerId): Collection;

    public function getStudentDetail(User $parent, int $studentId, ?int $centerId): User;

    public function requestLink(User $parent, string $studentPhone, ?int $centerId): ParentStudentLink;

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function autoLinkByPhone(User $parent, ?int $centerId): Collection;

    public function assertLinkedStudent(User $parent, int $studentId, ?int $centerId): ParentStudentLink;
}
