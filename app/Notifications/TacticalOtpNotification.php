<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TacticalOtpNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $otp;

    public function __construct($otp)
    {
        $this->otp = $otp;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('DAAS: Activation Intelligence Required')
                    ->greeting('Commander,')
                    ->line('A tactical activation has been initiated for your account.')
                    ->line('Use the following 6-digit intelligence code to verify your coordinates:')
                    ->line("**{$this->otp}**")
                    ->line('This code expires in 15 minutes.')
                    ->line('If you did not initiate this, abort immediately.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
