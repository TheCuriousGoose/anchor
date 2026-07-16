<?php

namespace App\Http\Requests;

use App\Enums\BoardRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShareBoardRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // No exists:users rule: an unknown address is an invitation, not a validation error.
        return [
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(BoardRole::class)],
        ];
    }
}
