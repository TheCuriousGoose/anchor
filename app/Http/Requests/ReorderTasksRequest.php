<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTasksRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'taskIds' => ['required', 'array', 'min:1'],
            'taskIds.*' => ['string', 'distinct'],
        ];
    }
}
