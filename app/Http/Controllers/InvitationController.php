<?php

namespace App\Http\Controllers;

use App\Models\BoardInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Landing point for the emailed invite link. Guests are sent to registration with the
     * address prefilled; CreateNewUser redeems by email once the account exists, so the
     * invitation survives even if they never come back to this URL.
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = BoardInvitation::query()->where('token', $token)->first();

        if ($invitation === null || $invitation->isExpired()) {
            return redirect()->route('home')->withErrors([
                'invitation' => __('That invitation link is no longer valid.'),
            ]);
        }

        $user = $request->user();

        if ($user === null) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Create your account to open ":board".', [
                    'board' => $invitation->board->name,
                ]),
            ]);

            return redirect()->route('register', ['email' => $invitation->email]);
        }

        if ($user->email !== $invitation->email) {
            return redirect()->route('dashboard')->withErrors([
                'invitation' => __('That invitation was sent to a different email address.'),
            ]);
        }

        BoardInvitation::redeemFor($user);

        return to_route('boards.show', $invitation->board);
    }
}
