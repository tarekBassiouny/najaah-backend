<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssignmentSubmission;
use App\Models\AssignmentSubmissionFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssignmentSubmissionFile>
 */
class AssignmentSubmissionFileFactory extends Factory
{
    protected $model = AssignmentSubmissionFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'doc', 'docx', 'jpg', 'png']);

        return [
            'assignment_submission_id' => AssignmentSubmission::factory(),
            'file_name' => fake()->word().'.'.$extension,
            'file_path' => 'assignments/'.fake()->uuid().'.'.$extension,
            'file_size_kb' => fake()->numberBetween(100, 10000),
            'file_type' => $this->getMimeType($extension),
            'storage_provider' => 'local',
        ];
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_name' => fake()->word().'.pdf',
            'file_path' => 'assignments/'.fake()->uuid().'.pdf',
            'file_type' => 'application/pdf',
        ]);
    }

    public function word(): static
    {
        return $this->state(fn (array $attributes): array => [
            'file_name' => fake()->word().'.docx',
            'file_path' => 'assignments/'.fake()->uuid().'.docx',
            'file_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function image(): static
    {
        $extension = fake()->randomElement(['jpg', 'png']);

        return $this->state(fn (array $attributes): array => [
            'file_name' => fake()->word().'.'.$extension,
            'file_path' => 'assignments/'.fake()->uuid().'.'.$extension,
            'file_type' => 'image/'.$extension,
        ]);
    }

    public function forSubmission(AssignmentSubmission $submission): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignment_submission_id' => $submission->id,
        ]);
    }

    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }
}
