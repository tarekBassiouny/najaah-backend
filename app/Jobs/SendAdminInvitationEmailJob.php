<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Center;
use App\Models\User;
use App\Notifications\AdminCenterOnboardingNotification;
use App\Services\Logging\LogContextResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class SendAdminInvitationEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $centerId, public readonly int $ownerId) {}

    public function handle(): void
    {
        $center = Center::find($this->centerId);
        $owner = User::find($this->ownerId);

        if (! $center instanceof Center || ! $owner instanceof User) {
            Log::warning('Admin invitation email skipped due to missing data.', $this->resolveLogContext([
                'source' => 'job',
                'center_id' => $this->centerId,
                'user_id' => $this->ownerId,
            ]));

            return;
        }

        if ($owner->invitation_sent_at !== null) {
            return;
        }

        if ($owner->email === null) {
            Log::warning('Admin invitation email skipped due to missing email.', $this->resolveLogContext([
                'source' => 'job',
                'center_id' => $center->id,
                'user_id' => $owner->id,
            ]));

            return;
        }

        $token = Password::broker()->createToken($owner);
        $owner->notify(new AdminCenterOnboardingNotification($center, $token));

        $owner->invitation_sent_at = now();
        $owner->save();
    }

    public function failed(\Throwable $exception): void
    {
        $center = Center::find($this->centerId);
        if ($center instanceof Center) {
            $center->onboarding_status = Center::ONBOARDING_FAILED;
            $center->save();
        }

        Log::error('Admin invitation email failed.', $this->resolveLogContext([
            'source' => 'job',
            'center_id' => $this->centerId,
            'user_id' => $this->ownerId,
            'error' => $exception->getMessage(),
        ]));
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
