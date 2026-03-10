<?php

declare(strict_types=1);

namespace App\Services\Assessments\Contracts;

use App\Models\AIGenerationJob;
use App\Models\Pdf;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Video;

interface AIQuizGeneratorServiceInterface
{
    /**
     * Generate quiz questions from a video transcript.
     */
    public function generateFromVideo(Quiz $quiz, Video $video, int $questionCount, User $creator): AIGenerationJob;

    /**
     * Generate quiz questions from a PDF document.
     */
    public function generateFromPdf(Quiz $quiz, Pdf $pdf, int $questionCount, User $creator): AIGenerationJob;

    /**
     * Process an AI generation job (called by queue worker).
     */
    public function processJob(AIGenerationJob $job): void;

    /**
     * Extract transcript from a video.
     */
    public function extractVideoTranscript(Video $video): ?string;

    /**
     * Extract text content from a PDF.
     */
    public function extractPdfContent(Pdf $pdf): ?string;

    /**
     * Approve generated questions and add them to the quiz.
     *
     * @param  array<int>|null  $questionIndexes  Indexes of questions to approve, null for all
     * @return int Number of questions added
     */
    public function approveQuestions(AIGenerationJob $job, ?array $questionIndexes = null): int;

    /**
     * Discard generated questions (delete the job).
     */
    public function discardQuestions(AIGenerationJob $job): void;

    /**
     * Get the status of an AI generation job.
     *
     * @return array<string, mixed>
     */
    public function getJobStatus(AIGenerationJob $job): array;
}
