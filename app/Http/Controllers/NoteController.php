<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Board;
use App\Models\Note;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;

class NoteController extends Controller
{
    public function store(StoreNoteRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $data = $request->validated();

        if (array_key_exists('body', $data)) {
            $data['body'] = HtmlSanitizer::clean($data['body']);
        }

        $note = $board->notes()->create($data);

        return response()->json($note, 201);
    }

    public function update(UpdateNoteRequest $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $data = $request->validated();

        if (array_key_exists('body', $data)) {
            $data['body'] = HtmlSanitizer::clean($data['body']);
        }

        $note->update($data);

        return response()->json($note);
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->authorize('delete', $note);
        $note->delete();

        return response()->json(status: 204);
    }
}
