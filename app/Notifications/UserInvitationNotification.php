<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public string $inviteUrl) {}

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
            ->subject('Invitation to Parañaque CSN Alert System')
            ->greeting("Hello {$notifiable->name},")
            ->line('You have been invited to join the Parañaque City Special Needs (CSN) Emergency Alert System.')
            ->line('Please click the button below to set up your account password and get started.')
            ->action('Set Up Your Password', $this->inviteUrl)
            ->line('This invitation link is valid for 3 days.')
            ->line('If you were not expecting this invitation, you may ignore this email.');
    }
}
