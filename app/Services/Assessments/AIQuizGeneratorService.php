<?php

declare(strict_types=1);

namespace App\Services\Assessments;

use App\Enums\AIGenerationStatus;
use App\Models\AIGenerationJob;
use App\Models\Pdf;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Models\Video;
use App\Services\Assessments\Contracts\AIQuizGeneratorServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AIQuizGeneratorService implements AIQuizGeneratorServiceInterface
{
    public function generateFromVideo(Quiz $quiz, Video $video, int $questionCount, User $creator): AIGenerationJob
    {
        return AIGenerationJob::create([
            'quiz_id' => $quiz->id,
            'source_type' => AIGenerationJob::SOURCE_TYPE_VIDEO,
            'source_id' => $video->id,
            'status' => AIGenerationStatus::Pending,
            'questions_requested' => $questionCount,
            'created_by' => $creator->id,
        ]);
    }

    public function generateFromPdf(Quiz $quiz, Pdf $pdf, int $questionCount, User $creator): AIGenerationJob
    {
        return AIGenerationJob::create([
            'quiz_id' => $quiz->id,
            'source_type' => AIGenerationJob::SOURCE_TYPE_PDF,
            'source_id' => $pdf->id,
            'status' => AIGenerationStatus::Pending,
            'questions_requested' => $questionCount,
            'created_by' => $creator->id,
        ]);
    }

    public function processJob(AIGenerationJob $job): void
    {
        try {
            $job->update([
                'status' => AIGenerationStatus::Processing,
                'started_at' => now(),
            ]);

            $content = match ($job->source_type) {
                AIGenerationJob::SOURCE_TYPE_VIDEO => $this->extractVideoTranscript(Video::findOrFail($job->source_id)),
                AIGenerationJob::SOURCE_TYPE_PDF => $this->extractPdfContent(Pdf::findOrFail($job->source_id)),
                default => null,
            };

            if ($content === null || trim($content) === '') {
                throw new \RuntimeException('Failed to extract content from source');
            }

            $prompt = $this->buildPrompt($content, $job->questions_requested);
            $questions = $this->callAIProvider($prompt);

            $job->update([
                'status' => AIGenerationStatus::Completed,
                'completed_at' => now(),
                'questions_generated' => count($questions),
                'generated_questions' => $questions,
                'prompt_used' => $prompt,
                'ai_provider' => config('services.ai.provider', 'openai'),
                'ai_model' => config('services.ai.model', 'gpt-4'),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('AI Quiz Generation Failed', [
                'job_id' => $job->id,
                'error' => $throwable->getMessage(),
            ]);

            $job->update([
                'status' => AIGenerationStatus::Failed,
                'completed_at' => now(),
                'error_message' => $throwable->getMessage(),
            ]);
        }
    }

    public function extractVideoTranscript(Video $video): ?string
    {
        // Try to get transcript from video's stored transcript field
        if (! empty($video->transcript)) {
            return $video->transcript;
        }

        // Try to fetch from Bunny Stream API if available
        $libraryId = config('services.bunny.library_id');
        $apiKey = config('services.bunny.api_key');

        if ($libraryId && $apiKey && $video->bunny_video_id) {
            try {
                $response = Http::withHeaders([
                    'AccessKey' => $apiKey,
                ])->get(sprintf('https://video.bunnycdn.com/library/%s/videos/%s', $libraryId, $video->bunny_video_id));

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['captions'][0]['text'] ?? null;
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to fetch video transcript from Bunny', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    public function extractPdfContent(Pdf $pdf): ?string
    {
        // This would typically use a PDF parsing library like Smalot/PdfParser
        // For now, we'll return a placeholder that should be implemented
        // based on the actual PDF storage and parsing requirements

        // If PDF has stored text content
        if (! empty($pdf->text_content)) {
            return $pdf->text_content;
        }

        // TODO: Implement actual PDF text extraction
        // Options:
        // 1. Use Smalot/PdfParser
        // 2. Use external service like AWS Textract
        // 3. Use stored text if PDF was pre-processed

        return null;
    }

    private function buildPrompt(string $content, int $questionCount): string
    {
        return <<<PROMPT
You are an educational assessment expert. Based on the following content, generate {$questionCount} multiple choice questions.

CONTENT:
{$content}

REQUIREMENTS:
- Each question should test understanding, not just recall
- Provide exactly 4 answer options labeled A, B, C, D
- Mark the correct answer(s)
- Include a brief explanation for why the answer is correct
- Questions should be clear and unambiguous
- Vary difficulty levels

OUTPUT FORMAT (JSON array):
[
  {
    "question": "Question text here?",
    "options": [
      {"label": "A", "text": "Option A text", "is_correct": false},
      {"label": "B", "text": "Option B text", "is_correct": true},
      {"label": "C", "text": "Option C text", "is_correct": false},
      {"label": "D", "text": "Option D text", "is_correct": false}
    ],
    "explanation": "Explanation of correct answer",
    "difficulty": "medium"
  }
]

Generate exactly {$questionCount} questions. Return only the JSON array, no additional text.
PROMPT;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function callAIProvider(string $prompt): array
    {
        $provider = config('services.ai.provider', 'openai');

        return match ($provider) {
            'anthropic' => $this->callAnthropic($prompt),
            default => $this->callOpenAI($prompt),
        };
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function callOpenAI(string $prompt): array
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.ai.model', 'gpt-4');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('OpenAI API request failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function callAnthropic(string $prompt): array
    {
        $apiKey = config('services.anthropic.api_key');
        $model = config('services.ai.model', 'claude-3-sonnet-20240229');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Anthropic API request failed: '.$response->body());
        }

        $content = $response->json('content.0.text');

        return $this->parseAIResponse($content);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function parseAIResponse(string $content): array
    {
        // Extract JSON from response (handle markdown code blocks)
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\n?/', '', $content);
            $content = preg_replace('/\n?```$/', '', $content);
        }

        $questions = json_decode($content, true);

        if (! is_array($questions)) {
            throw new \RuntimeException('Failed to parse AI response as JSON');
        }

        return $questions;
    }

    /**
     * @param  array<int>|null  $questionIndexes
     */
    public function approveQuestions(AIGenerationJob $job, ?array $questionIndexes = null): int
    {
        if (! $job->hasGeneratedQuestions()) {
            return 0;
        }

        return DB::transaction(function () use ($job, $questionIndexes): int {
            $questions = $job->generated_questions;
            $quiz = $job->quiz;
            $addedCount = 0;
            $maxOrder = $quiz->questions()->max('order_index') ?? -1;

            foreach ($questions as $index => $questionData) {
                if ($questionIndexes !== null && ! in_array($index, $questionIndexes, true)) {
                    continue;
                }

                $maxOrder++;

                $question = QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_translations' => ['en' => $questionData['question']],
                    'question_type' => 0, // SingleChoice
                    'explanation_translations' => isset($questionData['explanation'])
                        ? ['en' => $questionData['explanation']]
                        : null,
                    'points' => 1.00,
                    'order_index' => $maxOrder,
                    'is_active' => true,
                    'ai_generated' => true,
                    'ai_source_type' => $job->source_type,
                    'ai_source_id' => $job->source_id,
                ]);

                foreach ($questionData['options'] as $optionIndex => $option) {
                    QuizAnswer::create([
                        'quiz_question_id' => $question->id,
                        'answer_translations' => ['en' => $option['text']],
                        'is_correct' => $option['is_correct'] ?? false,
                        'order_index' => $optionIndex,
                    ]);
                }

                $addedCount++;
            }

            return $addedCount;
        });
    }

    public function discardQuestions(AIGenerationJob $job): void
    {
        $job->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJobStatus(AIGenerationJob $job): array
    {
        return [
            'id' => $job->id,
            'status' => $job->status->value,
            'status_label' => $job->status->label(),
            'source_type' => $job->source_type,
            'source_id' => $job->source_id,
            'questions_requested' => $job->questions_requested,
            'questions_generated' => $job->questions_generated,
            'ai_provider' => $job->ai_provider,
            'ai_model' => $job->ai_model,
            'error_message' => $job->error_message,
            'started_at' => $job->started_at?->toIso8601String(),
            'completed_at' => $job->completed_at?->toIso8601String(),
            'generated_questions' => $job->generated_questions,
        ];
    }
}
