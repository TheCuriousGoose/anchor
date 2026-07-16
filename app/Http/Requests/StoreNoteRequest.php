<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:20000'],
            'parent_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('notes', 'id')->where('board_id', $this->route('board')?->id),
            ],
        ];
    }
}
