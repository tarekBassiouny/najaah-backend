<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BulkJobStatus;
use App\Enums\WhatsAppCodeFormat;
use App\Models\BulkWhatsAppJob;
use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkWhatsAppJobFactory extends Factory
{
    protected $model = BulkWhatsAppJob::class;

    public function definition(): array
    {
        $center = Center::factory()->create();

        return [
            'center_id' => $center->id,
            'total_codes' => 10,
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => BulkJobStatus::Pending,
            'format' => WhatsAppCodeFormat::TextCode,
            'started_at' => null,
            'completed_at' => null,
            'created_by' => User::factory()->create([
                'is_student' => false,
                'center_id' => $center->id,
            ])->id,
            'settings' => [
                'delay_seconds' => 3,
                'batch_size' => 50,
                'batch_pause_seconds' => 60,
                'max_retries' => 2,
                'max_failures_before_pause' => 10,
            ],
        ];
    }
}
