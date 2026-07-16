<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Board;
use App\Models\Note;
use Illuminate\Http\JsonResponse;

class NoteController extends Controller
{
    public function store(StoreNoteRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $note = $board->notes()->create($request->validated());

        return response()->json($note, 201);
    }

    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $note->update($request->validated());

        return response()->json($note);
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->authorize('delete', $note);
        $note->delete();

        return response()->json(status: 204);
    }
}
