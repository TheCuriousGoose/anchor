<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBoardRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:8'],
            'tasks' => ['required', 'array', 'max:500'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.completed' => ['required', 'boolean'],
        ];
    }
}
