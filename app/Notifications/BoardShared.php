<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Board;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Queued mail is drained once per minute by the scheduler. */
class BoardShared extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Board $board,
        public User $sharedBy,
        public string $role,
    ) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return $notifiable->wantsNotification(NotificationType::BoardShared) ? ['mail'] : [];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':name shared ":board" with you', [
                'name' => $this->sharedBy->name,
                'board' => $this->board->name,
            ]))
            ->greeting(__('Hi :name,', ['name' => $notifiable->name]))
            ->line(__(':name has shared the board ":board" with you as :role.', [
                'name' => $this->sharedBy->name,
                'board' => $this->board->name,
                'role' => __($this->role),
            ]))
            ->action(__('Open board'), route('boards.show', $this->board))
            ->line(__('You can turn these emails off in your notification settings.'));
    }
}
