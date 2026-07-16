<?php

namespace App\Http\Controllers\Settings;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Notifications', [
            'preferences' => $request->user()->notificationPreferences(),
            'types' => array_map(fn (NotificationType $type) => $type->value, NotificationType::cases()),
        ]);
    }

    public function update(NotificationUpdateRequest $request): RedirectResponse
    {
        /** @var array<string, bool> $preferences */
        $preferences = $request->validated('preferences');

        $user = $request->user();

        // Stored whole rather than merged, so a type removed from the enum doesn't linger.
        $user->notification_preferences = collect(NotificationType::cases())
            ->mapWithKeys(fn (NotificationType $type) => [
                $type->value => (bool) ($preferences[$type->value] ?? true),
            ])
            ->all();

        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Notification settings saved.')]);

        return to_route('notifications.edit');
    }
}
