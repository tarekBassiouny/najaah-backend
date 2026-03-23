<?php

declare(strict_types=1);

namespace App\Services\Parents;

use App\Enums\ParentLinkMethod;
use App\Enums\ParentLinkStatus;
use App\Events\ParentLinked;
use App\Events\ParentLinkRequested;
use App\Exceptions\DomainException;
use App\Models\ParentStudentLink;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Parents\Contracts\ParentServiceInterface;
use App\Support\AuditActions;
use App\Support\ErrorCodes;
use Illuminate\Database\Eloquent\Collection;

class ParentService implements ParentServiceInterface
{
    public function __construct(
        private readonly AuditLogService $auditLogService
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
        /** @var User|null $student */
        $student = User::query()
            ->where('phone', $studentPhone)
            ->where('is_student', true)
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
            ->where('parent_phone', $parent->phone)
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
}
