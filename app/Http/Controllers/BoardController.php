<?php

namespace App\Http\Controllers;

use App\Enums\AuditAction;
use App\Events\BoardDeleted;
use App\Events\BoardListChanged;
use App\Events\BoardUpdated;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TasksReordered;
use App\Events\TaskUpdated;
use App\Http\Requests\ImportBoardRequest;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\StoreImageRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\BoardResource;
use App\Models\AuditLog;
use App\Models\Board;
use App\Models\Task;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardController extends Controller
{
    public function import(ImportBoardRequest $request): JsonResponse
    {
        $data = $request->validated();

        $board = DB::transaction(function () use ($request, $data): Board {
            $board = $request->user()->boards()->create([
                'name' => $data['name'],
                'icon' => $data['icon'] ?? '✓',
            ]);

            /** @var array<int, array{title: string, completed: bool}> $tasks */
            $tasks = $data['tasks'];

            $board->tasks()->createMany(
                collect($tasks)->map(fn (array $task, int $position): array => [
                    'title' => $task['title'],
                    'completed' => $task['completed'],
                    'position' => $position,
                ])->all(),
            );

            return $board;
        });

        return response()->json((new BoardResource($board->load(['tasks.labels', 'notes', 'collaborators', 'labels', 'invitations'])))->resolve(), 201);
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $data = $request->validated();

        $board = $request->user()->boards()->create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? '✓',
        ]);

        return response()->json((new BoardResource($board->load(['tasks.labels', 'notes', 'collaborators', 'labels', 'invitations'])))->resolve(), 201);
    }

    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $board->update($request->validated());

        broadcast(new BoardUpdated($board))->toOthers();
        broadcast(new BoardListChanged($board->memberIds()))->toOthers();

        return response()->json((new BoardResource($board->load(['tasks.labels', 'notes', 'collaborators', 'labels', 'invitations'])))->resolve());
    }

    public function destroy(Request $request, Board $board): JsonResponse
    {
        $this->authorize('delete', $board);

        // Both are captured before the delete: memberIds() can't be queried afterwards,
        // and the event must not hold a model that no longer exists.
        $memberIds = $board->memberIds();
        $name = $board->name;

        AuditLog::record(AuditAction::BoardDeleted, $board, $name, [
            'collaborators_affected' => count($memberIds) - 1,
        ]);

        $board->delete();

        broadcast(new BoardDeleted($board->id, $name))->toOthers();
        broadcast(new BoardListChanged($memberIds))->toOthers();

        return response()->json(status: 204);
    }

    public function storeTask(StoreTaskRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $data = $request->validated();

        $task = DB::transaction(function () use ($board, $data): Task {
            $position = $board->tasks()->lockForUpdate()->max('position');

            $task = $board->tasks()->create([
                'title' => $data['title'],
                'priority' => $data['priority'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'position' => ($position ?? -1) + 1,
            ]);

            if (! empty($data['label_ids'])) {
                $task->labels()->sync($board->labels()->whereIn('id', $data['label_ids'])->pluck('id'));
            }

            return $task;
        });

        // `refresh` so database-side defaults (`completed`) and untouched nullable columns
        // (`description`, `due_date`) are present rather than silently absent — a freshly
        // created model only holds the attributes that were explicitly set. Load once, then
        // broadcast and respond from the same instance so collaborators receive
        // byte-for-byte what the acting client got back.
        $task->refresh()->load('labels');

        broadcast(new TaskCreated($task))->toOthers();

        return response()->json($task, 201);
    }

    public function updateTask(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        if (array_key_exists('description', $data)) {
            $data['description'] = $data['description'] === null ? null : HtmlSanitizer::clean($data['description']);
        }

        $task->update(collect($data)->except('label_ids')->all());

        if (array_key_exists('label_ids', $data)) {
            $task->labels()->sync($task->board->labels()->whereIn('id', $data['label_ids'])->pluck('id'));
        }

        $task->load('labels');

        broadcast(new TaskUpdated($task))->toOthers();

        return response()->json($task);
    }

    public function destroyTask(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        [$boardId, $taskId] = [$task->board_id, $task->id];

        $task->delete();

        broadcast(new TaskDeleted($boardId, $taskId))->toOthers();

        return response()->json(status: 204);
    }

    public function storeTaskImage(StoreImageRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $media = $task->addMediaFromRequest('image')->toMediaCollection('content-images');

        return response()->json(['url' => $media->getUrl()], 201);
    }

    public function reorderTasks(ReorderTasksRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        /** @var array<int, string> $taskIds */
        $taskIds = $request->validated('taskIds');
        $boardTaskIds = $board->tasks()->pluck('id')->all();

        abort_unless(
            count($taskIds) === count($boardTaskIds) && empty(array_diff($taskIds, $boardTaskIds)),
            422,
        );

        DB::transaction(function () use ($board, $taskIds): void {
            foreach ($taskIds as $position => $taskId) {
                Task::where('id', $taskId)->where('board_id', $board->id)->update(['position' => $position]);
            }
        });

        broadcast(new TasksReordered($board->id, $taskIds))->toOthers();

        return response()->json($board->tasks()->get());
    }
}
