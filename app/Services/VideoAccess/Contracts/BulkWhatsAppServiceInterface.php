<?php

declare(strict_types=1);

namespace App\Services\VideoAccess\Contracts;

use App\Enums\WhatsAppCodeFormat;
use App\Models\BulkWhatsAppJob;
use App\Models\User;

interface BulkWhatsAppServiceInterface
{
    /**
     * @param  array<int, int|string>  $codeIds
     */
    public function initiate(User $admin, int $centerId, array $codeIds, WhatsAppCodeFormat $format): BulkWhatsAppJob;

    public function pause(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob;

    public function resume(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob;

    public function retryFailed(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob;

    public function cancel(User $admin, BulkWhatsAppJob $job): BulkWhatsAppJob;
}
