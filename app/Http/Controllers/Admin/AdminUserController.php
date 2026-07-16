<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserSuspensionRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->withCount('boards')
            ->when($search !== '', fn ($query) => $query->where(fn ($match) => $match
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'suspended' => $user->isSuspended(),
                'boardsCount' => $user->boards_count,
                'createdAt' => $user->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = UserRole::from((string) $request->validated('role'));

        $this->guardSelf($request, $user, 'You cannot change your own role.');

        if ($user->isAdmin() && $role !== UserRole::Admin) {
            $this->guardLastAdmin();
        }

        $previous = $user->role;

        if ($previous === $role) {
            return back();
        }

        // Assigned directly rather than via update(): role is deliberately not mass
        // assignable, so ProfileController's fill($request->validated()) can never be
        // used to self-promote.
        $user->role = $role;
        $user->save();

        AuditLog::record(AuditAction::UserRoleChanged, $user, $user->email, [
            'from' => $previous->value,
            'to' => $role->value,
        ]);

        return back();
    }

    public function updateSuspension(UpdateUserSuspensionRequest $request, User $user): RedirectResponse
    {
        $suspend = (bool) $request->validated('suspended');

        $this->guardSelf($request, $user, 'You cannot suspend your own account.');

        if ($suspend && $user->isAdmin()) {
            $this->guardLastAdmin();
        }

        $user->suspended_at = $suspend ? now() : null;
        $user->save();

        AuditLog::record(
            $suspend ? AuditAction::UserSuspended : AuditAction::UserUnsuspended,
            $user,
            $user->email,
        );

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->guardSelf($request, $user, 'Delete your own account from profile settings instead.');

        if ($user->isAdmin()) {
            $this->guardLastAdmin();
        }

        // boards.user_id is cascadeOnDelete, so this also destroys every board this user
        // owns — including ones shared with other active users. Recorded so the blast
        // radius is recoverable from the trail even though the boards are not.
        $ownedBoards = $user->boards()->withCount('collaborators')->get();

        AuditLog::record(AuditAction::UserDeleted, $user, $user->email, [
            'boards_deleted' => $ownedBoards->pluck('name')->all(),
            'collaborators_affected' => (int) $ownedBoards->sum('collaborators_count'),
        ]);

        $user->delete();

        return back();
    }

    private function guardSelf(Request $request, User $user, string $message): void
    {
        abort_if($request->user()?->id === $user->id, 422, $message);
    }

    /** Refuse to remove the last way into the admin area. */
    private function guardLastAdmin(): void
    {
        $remaining = User::query()
            ->where('role', UserRole::Admin)
            ->whereNull('suspended_at')
            ->count();

        abort_if($remaining <= 1, 422, 'This is the last active administrator.');
    }
}
