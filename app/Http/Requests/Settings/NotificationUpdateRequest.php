<?php

namespace App\Http\Requests\Settings;

use App\Enums\NotificationType;
use Illuminate\Foundation\Http\FormRequest;

class NotificationUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // `array:<keys>` rejects anything not backed by the enum, so an unknown key can't
        // be smuggled into the stored JSON.
        $types = implode(',', array_map(fn (NotificationType $type) => $type->value, NotificationType::cases()));

        return [
            'preferences' => ['required', 'array:'.$types],
            'preferences.*' => ['required', 'boolean'],
        ];
    }
}
