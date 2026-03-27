<?php

declare(strict_types=1);

namespace App\Services\Parents;

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Events\ParentLinkApproved;
use App\Events\ParentLinked;
use App\Events\ParentLinkRequested;
use App\Events\ParentLinkRevoked;
use App\Exceptions\DomainException;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Parents\Contracts\ParentServiceInterface;
use App\Services\Phone\PhoneNormalizer;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use App\Support\PhoneSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ParentService implements ParentServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly PhoneNormalizer $phoneNormalizer,
        private readonly PhoneSearch $phoneSearch
    ) {}

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function getLinkedStudents(User $parent, ?int $centerId): Collection
    {
        return ParentStudentLink::query()
            ->with(['student:id,name,phone,status,center_id'])
            ->forParent($parent->id)
            ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
            ->active()
            ->get();
    }

    public function getStudentDetail(User $parent, int $studentId, ?int $centerId): User
    {
        $this->assertLinkedStudent($parent, $studentId, $centerId);

        /** @var User $student */
        $student = User::query()
            ->with(['center', 'grade', 'school', 'college'])
            ->findOrFail($studentId);

        return $student;
    }

    public function requestLink(User $parent, string $studentPhone, ?int $centerId): ParentStudentLink
    {
        $normalizedPhone = $this->phoneNormalizer->normalize($studentPhone, $parent->country_code);

        /** @var User|null $student */
        $student = User::query()
            ->where('is_student', true)
            ->where(function ($query) use ($normalizedPhone, $studentPhone): void {
                if ($normalizedPhone !== null) {
                    $query->where('phone_normalized', $normalizedPhone)
                        ->orWhere('phone', $studentPhone);

                    return;
                }

                $query->where('phone', $studentPhone);
            })
            ->when(is_numeric($centerId), fn ($q) => $q->where('center_id', $centerId))
            ->first();

        if ($student === null) {
            throw new DomainException('No student found with this phone number.', ErrorCodes::STUDENT_NOT_LINKED, 404);
        }

        $existing = ParentStudentLink::query()
            ->forParent($parent->id)
            ->forStudent($student->id)
            ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
            ->whereIn('status', [ParentLinkStatus::Active->value, ParentLinkStatus::PendingApproval->value])
            ->first();

        if ($existing !== null) {
            throw new DomainException('Link already exists for this student.', ErrorCodes::LINK_ALREADY_EXISTS, 422);
        }

        /** @var ParentStudentLink $link */
        $link = ParentStudentLink::create([
            'parent_user_id' => $parent->id,
            'student_user_id' => $student->id,
            'center_id' => $centerId,
            'status' => ParentLinkStatus::PendingApproval,
            'link_method' => ParentLinkMethod::ParentRequested,
            'linked_at' => now(),
        ]);

        $this->auditLogService->log($parent, $link, AuditActions::PARENT_LINK_REQUESTED, [
            'student_id' => $student->id,
            'student_phone' => $studentPhone,
        ]);

        ParentLinkRequested::dispatch($link);

        return $link->load('student:id,name,phone');
    }

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function autoLinkByPhone(User $parent, ?int $centerId): Collection
    {
        $students = User::query()
            ->where(function ($query) use ($parent): void {
                if ($parent->phone_normalized !== null) {
                    $query->where('parent_phone_normalized', $parent->phone_normalized);
                }

                $query->orWhere('parent_phone', $parent->phone);
            })
            ->where('is_student', true)
            ->when(is_numeric($centerId), fn ($q) => $q->where('center_id', $centerId))
            ->get();

        $links = new Collection;

        foreach ($students as $student) {
            $existing = ParentStudentLink::query()
                ->forParent($parent->id)
                ->forStudent($student->id)
                ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
                ->first();

            if ($existing !== null) {
                continue;
            }

            /** @var ParentStudentLink $link */
            $link = ParentStudentLink::create([
                'parent_user_id' => $parent->id,
                'student_user_id' => $student->id,
                'center_id' => $centerId,
                'status' => ParentLinkStatus::Active,
                'link_method' => ParentLinkMethod::AutoMatched,
                'linked_at' => now(),
            ]);

            $this->auditLogService->log($parent, $link, AuditActions::PARENT_LINK_AUTO_MATCHED, [
                'student_id' => $student->id,
                'reason' => 'phone_match',
            ]);

            ParentLinked::dispatch($link);

            $links->push($link);
        }

        return $links;
    }

    public function assertLinkedStudent(User $parent, int $studentId, ?int $centerId): ParentStudentLink
    {
        /** @var ParentStudentLink|null $link */
        $link = ParentStudentLink::query()
            ->forParent($parent->id)
            ->where('student_user_id', $studentId)
            ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
            ->active()
            ->first();

        if ($link === null) {
            throw new DomainException('Student is not linked to this parent.', ErrorCodes::STUDENT_NOT_LINKED, 403);
        }

        return $link;
    }

    // ── Admin methods ──

    public function createLink(User $admin, int $parentUserId, int $studentUserId, ?int $centerId): ParentStudentLink
    {
        $existing = ParentStudentLink::query()
            ->forParent($parentUserId)
            ->forStudent($studentUserId)
            ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
            ->whereIn('status', [ParentLinkStatus::Active->value, ParentLinkStatus::PendingApproval->value])
            ->first();

        if ($existing !== null) {
            throw new DomainException('Link already exists for this student.', ErrorCodes::LINK_ALREADY_EXISTS, 422);
        }

        /** @var ParentStudentLink $link */
        $link = ParentStudentLink::create([
            'parent_user_id' => $parentUserId,
            'student_user_id' => $studentUserId,
            'center_id' => $centerId,
            'status' => ParentLinkStatus::Active,
            'link_method' => ParentLinkMethod::AdminManaged,
            'linked_by' => $admin->id,
            'linked_at' => now(),
        ]);

        $this->auditLogService->log($admin, $link, AuditActions::PARENT_LINK_CREATED, [
            'parent_user_id' => $parentUserId,
            'student_user_id' => $studentUserId,
        ]);

        ParentLinked::dispatch($link);

        return $link->load(['parent:id,name,phone', 'student:id,name,phone']);
    }

    public function approveLink(User $admin, int $linkId): ParentStudentLink
    {
        /** @var ParentStudentLink $link */
        $link = ParentStudentLink::findOrFail($linkId);

        if ($link->status !== ParentLinkStatus::PendingApproval) {
            throw new DomainException('Only pending links can be approved.', ErrorCodes::PARENT_LINK_NOT_FOUND, 422);
        }

        $link->update([
            'status' => ParentLinkStatus::Active,
            'linked_by' => $admin->id,
        ]);

        $this->auditLogService->log($admin, $link, AuditActions::PARENT_LINK_APPROVED);

        ParentLinkApproved::dispatch($link);

        return $link->load(['parent:id,name,phone', 'student:id,name,phone']);
    }

    public function rejectLink(User $admin, int $linkId): ParentStudentLink
    {
        /** @var ParentStudentLink $link */
        $link = ParentStudentLink::findOrFail($linkId);

        if ($link->status !== ParentLinkStatus::PendingApproval) {
            throw new DomainException('Only pending links can be rejected.', ErrorCodes::PARENT_LINK_NOT_FOUND, 422);
        }

        $link->update(['status' => ParentLinkStatus::Revoked]);

        $this->auditLogService->log($admin, $link, AuditActions::PARENT_LINK_REVOKED, [
            'reason' => 'admin_rejected',
        ]);

        ParentLinkRevoked::dispatch($link);

        return $link->load(['parent:id,name,phone', 'student:id,name,phone']);
    }

    public function revokeLink(User $admin, int $linkId): ParentStudentLink
    {
        /** @var ParentStudentLink $link */
        $link = ParentStudentLink::findOrFail($linkId);

        if ($link->status === ParentLinkStatus::Revoked) {
            throw new DomainException('Link is already revoked.', ErrorCodes::PARENT_LINK_NOT_FOUND, 422);
        }

        $link->update(['status' => ParentLinkStatus::Revoked]);

        $this->auditLogService->log($admin, $link, AuditActions::PARENT_LINK_REVOKED, [
            'reason' => 'admin_revoked',
        ]);

        ParentLinkRevoked::dispatch($link);

        return $link->load(['parent:id,name,phone', 'student:id,name,phone']);
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function listParents(?int $centerId, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->where('is_parent', true)
            ->when(is_numeric($centerId), fn ($q) => $q->where('center_id', $centerId))
            ->when($search !== null && $search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('phone', 'like', sprintf('%%%s%%', $search));

                $this->phoneSearch->applyUserPhoneLike($q, $search);
            }))
            ->withCount(['parentLinks as active_links_count' => fn ($q) => $q->active()])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function listLinksForStudent(int $studentId): Collection
    {
        return ParentStudentLink::query()
            ->with(['parent:id,name,phone', 'linkedByUser:id,name'])
            ->forStudent($studentId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Collection<int, ParentStudentLink>
     */
    public function listPendingRequests(?int $centerId): Collection
    {
        return ParentStudentLink::query()
            ->with(['parent:id,name,phone', 'student:id,name,phone'])
            ->where('status', ParentLinkStatus::PendingApproval)
            ->when(is_numeric($centerId), fn ($q) => $q->forCenter($centerId))
            ->orderByDesc('created_at')
            ->get();
    }
}
