<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $code,
        protected string $context
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->context === 'registration'
            ? 'Verify your FinTrack account'
            : 'Your FinTrack verification code';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Use the verification code below to continue:')
            ->line('')
            ->line('**' . $this->code . '**')
            ->line('')
            ->line('This code expires in 10 minutes.')
            ->line('If you did not request this, please ignore this email.');
    }
}
