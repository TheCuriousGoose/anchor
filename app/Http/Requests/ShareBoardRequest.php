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
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::enum(BoardRole::class)],
        ];
    }
}
