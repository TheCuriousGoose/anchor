<?php

namespace App\Notifications;

use App\Models\BoardInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Goes to an email address with no account behind it, so it is delivered as an on-demand
 * notification (Notification::route('mail', ...)) and the notifiable is anonymous — there
 * are no preferences to consult, because there is no user yet to hold them.
 */
class BoardInvitationSent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BoardInvitation $invitation) {}

    /** @return array<int, string> */
    public function via(AnonymousNotifiable $notifiable): array
    {
        return ['mail'];
    }

    /** @return array<string, string> */
    public function viaQueues(): array
    {
        return ['mail' => 'mail'];
    }

    public function toMail(AnonymousNotifiable $notifiable): MailMessage
    {
        $invitation = $this->invitation->loadMissing(['board', 'inviter']);

        return (new MailMessage)
            ->subject(__(':name invited you to ":board" on AnchorNotes', [
                'name' => $invitation->inviter->name,
                'board' => $invitation->board->name,
            ]))
            ->greeting(__('Hi,'))
            ->line(__('**:name** invited you to collaborate on **":board"** as **:role**.', [
                'name' => $invitation->inviter->name,
                'board' => $invitation->board->name,
                'role' => __($invitation->role),
            ]))
            ->line(__('Create an account with this email address and the board will be waiting for you.'))
            ->action(__('Accept invitation'), route('invitations.accept', $invitation->token))
            ->line(__('This invitation expires in :days days.', [
                'days' => BoardInvitation::EXPIRES_AFTER_DAYS,
            ]));
    }
}
