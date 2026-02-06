<?php

declare(strict_types=1);

namespace App\Agents;

use App\Enums\AgentType;
use App\Enums\CourseStatus;
use App\Enums\VideoLifecycleStatus;
use App\Models\AgentExecution;
use App\Models\Course;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Centers\CenterScopeService;
use App\Support\AuditActions;
use Illuminate\Database\Eloquent\Model;

/**
 * Content Publishing Agent
 *
 * Automates the course publishing workflow:
 * 1. Validates course has sections
 * 2. Validates all videos are ready (encoded)
 * 3. Validates all PDFs are ready
 * 4. Verifies center ownership
 * 5. Updates course status to Published
 * 6. Creates audit log
 */
final class ContentPublishingAgent extends AbstractWorkflowAgent
{
    public function __construct(
        CenterScopeService $centerScopeService,
        AuditLogService $auditLogService
    ) {
        parent::__construct($centerScopeService, $auditLogService);
    }

    public function getType(): AgentType
    {
        return AgentType::ContentPublishing;
    }

    public function getName(): string
    {
        return 'Content Publishing';
    }

    public function getDescription(): string
    {
        return 'Validates and publishes a course, ensuring all content is ready for students.';
    }

    /**
     * @return array<int, string>
     */
    public function getSteps(): array
    {
        return [
            'validate_sections',
            'validate_videos',
            'validate_pdfs',
            'verify_center',
            'publish_course',
            'create_audit_log',
        ];
    }

    /**
     * @return class-string<Course>
     */
    public function getTargetClass(): string
    {
        return Course::class;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string[]>
     */
    public function validateContext(array $context): array
    {
        $errors = [];

        if (! isset($context['course_id'])) {
            $errors['course_id'] = ['Course ID is required.'];
        } elseif (! is_numeric($context['course_id'])) {
            $errors['course_id'] = ['Course ID must be numeric.'];
        }

        return $errors;
    }

    /**
     * @return array<string, string[]>
     */
    public function validateTarget(Model $target): array
    {
        $errors = [];

        if (! $target instanceof Course) {
            $errors['target'] = ['Target must be a Course.'];

            return $errors;
        }

        if ($target->status === CourseStatus::Published) {
            $errors['course'] = ['Course is already published.'];
        }

        if ($target->status === CourseStatus::Archived) {
            $errors['course'] = ['Cannot publish an archived course.'];
        }

        return $errors;
    }

    public function canExecute(User $actor): bool
    {
        // Check if user has course publish permission
        return true; // Implement permission check logic as needed
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function executeStep(AgentExecution $execution, string $step, Model $target, array $context): array
    {
        /** @var Course $course */
        $course = $target;

        return match ($step) {
            'validate_sections' => $this->validateSections($course),
            'validate_videos' => $this->validateVideos($course),
            'validate_pdfs' => $this->validatePdfs($course),
            'verify_center' => $this->verifyCenter($course, $execution),
            'publish_course' => $this->publishCourse($course),
            'create_audit_log' => $this->createAuditLog($execution, $course),
            default => throw new \InvalidArgumentException('Unknown step: '.$step),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function resolveTarget(array $context): Model
    {
        $courseId = (int) $context['course_id'];

        $course = Course::with(['sections', 'videos', 'pdfs'])->findOrFail($courseId);

        // Validate target before proceeding
        $errors = $this->validateTarget($course);
        if (! empty($errors)) {
            throw new \InvalidArgumentException(json_encode($errors) ?: 'Invalid target');
        }

        return $course;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSections(Course $course): array
    {
        $sections = $course->sections;

        if ($sections->isEmpty()) {
            throw new \RuntimeException('Course must have at least one section.');
        }

        return [
            'sections_count' => $sections->count(),
            'validated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVideos(Course $course): array
    {
        $videos = $course->videos;

        if ($videos->isEmpty()) {
            return [
                'videos_count' => 0,
                'validated' => true,
                'message' => 'No videos in course.',
            ];
        }

        $notReady = $videos->filter(function ($video): bool {
            return $video->lifecycle_status !== VideoLifecycleStatus::Ready;
        });

        if ($notReady->isNotEmpty()) {
            $notReadyIds = $notReady->pluck('id')->toArray();
            throw new \RuntimeException(
                'Some videos are not ready: '.implode(', ', $notReadyIds)
            );
        }

        return [
            'videos_count' => $videos->count(),
            'validated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePdfs(Course $course): array
    {
        $pdfs = $course->pdfs;

        if ($pdfs->isEmpty()) {
            return [
                'pdfs_count' => 0,
                'validated' => true,
                'message' => 'No PDFs in course.',
            ];
        }

        // Check all PDFs have valid file paths
        $invalid = $pdfs->filter(function ($pdf): bool {
            return empty($pdf->file_path);
        });

        if ($invalid->isNotEmpty()) {
            $invalidIds = $invalid->pluck('id')->toArray();
            throw new \RuntimeException(
                'Some PDFs are missing files: '.implode(', ', $invalidIds)
            );
        }

        return [
            'pdfs_count' => $pdfs->count(),
            'validated' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function verifyCenter(Course $course, AgentExecution $execution): array
    {
        if ($course->center_id !== $execution->center_id) {
            throw new \RuntimeException('Course does not belong to the specified center.');
        }

        return [
            'center_id' => $course->center_id,
            'verified' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publishCourse(Course $course): array
    {
        $previousStatus = $course->status;

        $course->status = CourseStatus::Published;
        $course->save();

        return [
            'previous_status' => $previousStatus->value ?? $previousStatus,
            'new_status' => CourseStatus::Published->value,
            'published' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createAuditLog(AgentExecution $execution, Course $course): array
    {
        $initiator = $execution->initiator;

        $this->auditLogService->log(
            $initiator,
            $course,
            AuditActions::COURSE_PUBLISHED,
            [
                'agent_execution_id' => $execution->id,
                'agent_type' => $this->getType()->value,
            ]
        );

        return [
            'audit_logged' => true,
        ];
    }
}
