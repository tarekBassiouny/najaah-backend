<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BulkItemStatus;
use App\Models\BulkWhatsAppJob;
use App\Models\BulkWhatsAppJobItem;
use App\Models\VideoAccessCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkWhatsAppJobItemFactory extends Factory
{
    protected $model = BulkWhatsAppJobItem::class;

    public function definition(): array
    {
        /** @var BulkWhatsAppJob $job */
        $job = BulkWhatsAppJob::factory()->create();
        /** @var VideoAccessCode $code */
        $code = VideoAccessCode::factory()->create([
            'center_id' => $job->center_id,
        ]);

        return [
            'bulk_job_id' => $job->id,
            'video_access_code_id' => $code->id,
            'status' => BulkItemStatus::Pending,
            'error' => null,
            'sent_at' => null,
            'attempts' => 0,
        ];
    }
}
