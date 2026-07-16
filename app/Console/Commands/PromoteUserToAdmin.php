<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Bootstraps the first administrator. Nothing in the app's own UI can mint an admin
 * out of nothing — promotion requires an existing admin — so the chain has to start
 * from the CLI.
 */
class PromoteUserToAdmin extends Command
{
    protected $signature = 'admin:promote {email : The email address of the user to promote}
                            {--demote : Demote the user back to a regular account instead}';

    protected $description = 'Grant or revoke application administrator access for a user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with the email address [{$email}].");

            return self::FAILURE;
        }

        $role = $this->option('demote') ? UserRole::User : UserRole::Admin;

        if ($user->role === $role) {
            $this->line("{$user->email} is already {$role->value}. Nothing to do.");

            return self::SUCCESS;
        }

        $previous = $user->role;

        // Not mass assignable by design — see AdminUserController::updateRole.
        $user->role = $role;
        $user->save();

        AuditLog::record(AuditAction::UserRoleChanged, $user, $user->email, [
            'from' => $previous->value,
            'to' => $role->value,
            'via' => 'console',
        ]);

        $this->info("{$user->email} is now {$role->value}.");

        return self::SUCCESS;
    }
}
