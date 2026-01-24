<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PdfUploadStatus;
use App\Models\Center;
use App\Models\PdfUploadSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PdfUploadSessionFactory extends Factory
{
    protected $model = PdfUploadSession::class;

    public function definition(): array
    {
        $center = Center::factory();
        $statuses = PdfUploadStatus::cases();

        return [
            'center_id' => $center,
            'created_by' => User::factory()->for($center, 'center'),
            'object_key' => 'centers/1/pdfs/demo.pdf',
            'upload_status' => $this->faker->randomElement($statuses),
            'error_message' => null,
            'file_extension' => 'pdf',
            'file_size_kb' => 1024,
            'expires_at' => now()->addMinutes(10),
        ];
    }
}
