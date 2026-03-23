<?php

declare(strict_types=1);

namespace App\Services\Parents\Contracts;

use App\Models\ParentStudentLink;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    // ── Admin methods ──

    public function createLink(User $admin, int $parentUserId, int $studentUserId, ?int $centerId): ParentStudentLink;

    public function approveLink(User $admin, int $linkId): ParentStudentLink;

    public function rejectLink(User $admin, int $linkId): ParentStudentLink;

    public function revokeLink(User $admin, int $linkId): ParentStudentLink;

    /**
     * @return LengthAwarePaginator<User>
     */
    public function listParents(?int $centerId, ?string $search, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function listLinksForStudent(int $studentId): Collection;

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function listPendingRequests(?int $centerId): Collection;
}
