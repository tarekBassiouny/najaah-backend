<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use App\Services\Logging\LogContextResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class SendAdminPasswordResetEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $userId,
        public readonly bool $isInvite = false
    ) {
        $this->onConnection((string) config('mail.queue_connection', 'database'));
        $this->onQueue((string) config('mail.queue', 'mail'));
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user instanceof User) {
            Log::warning('Admin password reset email skipped due to missing user.', $this->resolveLogContext([
                'source' => 'job',
                'user_id' => $this->userId,
                'is_invite' => $this->isInvite,
            ]));

            return;
        }

        if ($user->is_student || $user->email === null) {
            Log::warning('Admin password reset email skipped due to invalid target.', $this->resolveLogContext([
                'source' => 'job',
                'user_id' => $user->id,
                'center_id' => $user->center_id,
                'email' => $user->email,
                'is_invite' => $this->isInvite,
            ]));

            return;
        }

        if ($this->isInvite && $user->invitation_sent_at !== null) {
            return;
        }

        $user->loadMissing('center');
        $token = Password::broker()->createToken($user);
        $user->notify(new AdminPasswordResetNotification($token, $this->isInvite));

        if ($this->isInvite && $user->invitation_sent_at === null) {
            $user->invitation_sent_at = now();
            $user->save();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Admin password reset email job failed.', $this->resolveLogContext([
            'source' => 'job',
            'user_id' => $this->userId,
            'is_invite' => $this->isInvite,
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
