<?php

namespace App\Http\Requests;

use App\Models\Board;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $board = $this->route('board');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string', 'max:20000'],
            'parent_id' => [
                'sometimes',
                'nullable',
                'uuid',
                // Scoped to this board so a note can't be parented to another board's note.
                // A missing binding leaves this null, which matches nothing and fails closed.
                Rule::exists('notes', 'id')->where('board_id', $board instanceof Board ? $board->id : null),
            ],
        ];
    }
}
