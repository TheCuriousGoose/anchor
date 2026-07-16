<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Board;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BoardRoleChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Board $board,
        public User $changedBy,
        public string $role,
    ) {}

    /** @return array<int, string> */
    public function via(User $notifiable): array
    {
        return $notifiable->wantsNotification(NotificationType::BoardRoleChanged) ? ['mail'] : [];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your role on ":board" changed', ['board' => $this->board->name]))
            ->greeting(__('Hi :name,', ['name' => $notifiable->name]))
            ->line(__(':name changed your role on ":board" to :role.', [
                'name' => $this->changedBy->name,
                'board' => $this->board->name,
                'role' => __($this->role),
            ]))
            ->action(__('Open board'), route('boards.show', $this->board))
            ->line(__('You can turn these emails off in your notification settings.'));
    }
}
