<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Http\JsonResponse;

class LabelController extends Controller
{
    public function store(StoreLabelRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $label = $board->labels()->create($request->validated());

        return response()->json($label, 201);
    }

    public function update(UpdateLabelRequest $request, Label $label): JsonResponse
    {
        $this->authorize('update', $label);

        $label->update($request->validated());

        return response()->json($label);
    }

    public function destroy(Label $label): JsonResponse
    {
        $this->authorize('delete', $label);
        $label->delete();

        return response()->json(status: 204);
    }
}
