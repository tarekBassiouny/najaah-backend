<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordResetNotification extends Notification
{
    public function __construct(
        private readonly string $token,
        private readonly bool $isInvite = false
    ) {}

    /**
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $email = urlencode((string) $notifiable->email);
        $resetUrl = $appUrl.'/admin/reset-password?token='.$this->token.'&email='.$email;
        $loginUrl = $appUrl.'/admin/login';

        if ($this->isInvite) {
            return (new MailMessage)
                ->subject('Admin access invitation')
                ->greeting('Hello!')
                ->line('You have been invited to the admin portal.')
                ->line('Please set your password to activate your account.')
                ->action('Set your password', $resetUrl)
                ->line('Login URL: '.$loginUrl);
        }

        return (new MailMessage)
            ->subject('Reset your admin password')
            ->greeting('Hello!')
            ->line('We received a request to reset your admin password.')
            ->action('Reset password', $resetUrl)
            ->line('If you did not request this, no further action is required.');
    }
}
