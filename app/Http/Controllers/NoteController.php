<?php

namespace App\Http\Controllers;

use App\Events\NoteCreated;
use App\Events\NoteDeleted;
use App\Events\NoteUpdated;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Board;
use App\Models\Note;
use App\Support\HtmlSanitizer;
use App\Support\MediaUrl;
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

        broadcast(new NoteCreated($note))->toOthers();

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

        broadcast(new NoteUpdated($note))->toOthers();

        return response()->json($note);
    }

    public function destroy(Note $note): JsonResponse
    {
        $this->authorize('delete', $note);

        [$boardId, $noteId] = [$note->board_id, $note->id];

        $note->delete();

        broadcast(new NoteDeleted($boardId, $noteId))->toOthers();

        return response()->json(status: 204);
    }

    public function storeImage(StoreImageRequest $request, Note $note): JsonResponse
    {
        $this->authorize('update', $note);

        $media = $note->addMediaFromRequest('image')->toMediaCollection('content-images');

        return response()->json(['url' => MediaUrl::for($media)], 201);
    }
}
