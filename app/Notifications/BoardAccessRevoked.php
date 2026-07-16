<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BoardAccessRevoked extends Notification implements ShouldQueue
{
    use Queueable;

    /** Takes the board name, not the Board: by send time the recipient can no longer read it. */
    public function __construct(
        public string $boardName,
        public User $revokedBy,
    ) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return $notifiable->wantsNotification(NotificationType::BoardAccessRevoked) ? ['mail'] : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        // Deliberately no action button — there is nothing left for them to open.
        return (new MailMessage)
            ->subject(__('Your access to ":board" was removed', ['board' => $this->boardName]))
            ->greeting(__('Hi :name,', ['name' => $notifiable->name]))
            ->line(__(':name removed your access to the board ":board".', [
                'name' => $this->revokedBy->name,
                'board' => $this->boardName,
            ]))
            ->line(__('You can turn these emails off in your notification settings.'));
    }
}
