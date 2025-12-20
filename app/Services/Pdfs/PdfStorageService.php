<?php

declare(strict_types=1);

namespace App\Services\Pdfs;

use App\Actions\Concerns\NormalizesTranslations;
use App\Models\Course;
use App\Models\Pdf;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use App\Services\Courses\CourseAttachmentService;
use App\Services\Logging\LogContextResolver;
use App\Services\Sections\SectionAttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PdfStorageService
{
    use NormalizesTranslations;

    public const SOURCE_TYPE_URL = 0;

    public const SOURCE_TYPE_NATIVE = 1;

    public function __construct(
        private readonly CourseAttachmentService $courseAttachmentService,
        private readonly SectionAttachmentService $sectionAttachmentService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function upload(
        UploadedFile $file,
        array $data,
        ?User $creator,
        ?Course $course = null,
        ?Section $section = null,
        ?Video $video = null
    ): Pdf {
        $path = $file->store('pdfs', 'local');

        if ($path === false) {
            Log::error('PDF storage failed.', $this->resolveLogContext([
                'source' => 'api',
                'user_id' => $creator?->id,
                'center_id' => $course?->center_id ?? $creator?->center_id,
            ]));
            throw new RuntimeException('Failed to store PDF file.');
        }

        $fileSize = $file->getSize() !== false ? (int) ceil($file->getSize() / 1024) : null;
        $extension = $file->getClientOriginalExtension() ?: $file->extension();

        $payload = $this->normalizeTranslations($data, [
            'title_translations',
            'description_translations',
        ]);

        $payload['source_type'] = self::SOURCE_TYPE_NATIVE;
        $payload['source_provider'] = 'local';
        $payload['source_id'] = $path;
        $payload['source_url'] = null;
        $payload['file_size_kb'] = $fileSize;
        $payload['file_extension'] = $extension;
        $payload['created_by'] = $creator?->id;

        /** @var Pdf $pdf */
        $pdf = Pdf::create($payload);

        if ($course !== null) {
            $this->attachPdf($pdf, $course, $section, $video, $creator);
        }

        return $pdf;
    }

    private function attachPdf(Pdf $pdf, Course $course, ?Section $section, ?Video $video, ?User $creator): void
    {
        if ($section !== null && (int) $section->course_id !== (int) $course->id) {
            throw ValidationException::withMessages([
                'section_id' => ['Section does not belong to the provided course.'],
            ]);
        }

        if ($video !== null && ! $course->videos()->whereKey($video->id)->exists()) {
            throw ValidationException::withMessages([
                'video_id' => ['Video does not belong to the provided course.'],
            ]);
        }

        if ($section !== null) {
            $this->sectionAttachmentService->movePdfToSection($pdf, $section);
        } else {
            if (! $creator instanceof User) {
                throw ValidationException::withMessages([
                    'creator' => ['PDF attachments require an authenticated user.'],
                ]);
            }

            $this->courseAttachmentService->assignPdf($course, (int) $pdf->id, $creator);
        }

        if ($video !== null) {
            $pivot = $course->pdfs()
                ->where('pdf_id', $pdf->id)
                ->whereNull('course_pdf.deleted_at')
                ->first()?->pivot;

            if ($pivot !== null) {
                $pivot->video_id = $video->id;
                $pivot->save();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function resolveLogContext(array $overrides = []): array
    {
        return app(LogContextResolver::class)->resolve($overrides);
    }
}
