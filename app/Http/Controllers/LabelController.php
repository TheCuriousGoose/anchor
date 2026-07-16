<?php

namespace App\Http\Controllers;

use App\Events\LabelCreated;
use App\Events\LabelDeleted;
use App\Events\LabelUpdated;
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

        broadcast(new LabelCreated($label))->toOthers();

        return response()->json($label, 201);
    }

    public function update(UpdateLabelRequest $request, Label $label): JsonResponse
    {
        $this->authorize('update', $label);

        $label->update($request->validated());

        broadcast(new LabelUpdated($label))->toOthers();

        return response()->json($label);
    }

    public function destroy(Label $label): JsonResponse
    {
        $this->authorize('delete', $label);

        [$boardId, $labelId] = [$label->board_id, $label->id];

        $label->delete();

        broadcast(new LabelDeleted($boardId, $labelId))->toOthers();

        return response()->json(status: 204);
    }
}
