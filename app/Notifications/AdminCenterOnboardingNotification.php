<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Center;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminCenterOnboardingNotification extends Notification
{
    public function __construct(
        private readonly Center $center,
        private readonly string $token
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

        return (new MailMessage)
            ->subject('Center access granted')
            ->greeting('Hello!')
            ->line('You have been added as an owner for '.$this->center->name.' center.')
            ->line('Please set your password to access the admin portal.')
            ->action('Set your password', $resetUrl)
            ->line('Login URL: '.$loginUrl);
    }
}
