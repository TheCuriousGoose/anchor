<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportBoardRequest;
use App\Http\Requests\ReorderTasksRequest;
use App\Http\Requests\StoreBoardRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateBoardRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\BoardResource;
use App\Models\Board;
use App\Models\Task;
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

        return response()->json((new BoardResource($board->load(['tasks', 'notes', 'collaborators'])))->resolve(), 201);
    }

    public function store(StoreBoardRequest $request): JsonResponse
    {
        $data = $request->validated();

        $board = $request->user()->boards()->create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? '✓',
        ]);

        return response()->json((new BoardResource($board->load(['tasks', 'notes', 'collaborators'])))->resolve(), 201);
    }

    public function update(UpdateBoardRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $board->update($request->validated());

        return response()->json((new BoardResource($board->load(['tasks', 'notes', 'collaborators'])))->resolve());
    }

    public function destroy(Request $request, Board $board): JsonResponse
    {
        $this->authorize('delete', $board);
        $board->delete();

        return response()->json(status: 204);
    }

    public function storeTask(StoreTaskRequest $request, Board $board): JsonResponse
    {
        $this->authorize('update', $board);

        $data = $request->validated();

        $task = DB::transaction(function () use ($board, $data): Task {
            $position = $board->tasks()->lockForUpdate()->max('position');

            return $board->tasks()->create([
                'title' => $data['title'],
                'priority' => $data['priority'] ?? null,
                'position' => ($position ?? -1) + 1,
            ]);
        });

        return response()->json($task, 201);
    }

    public function updateTask(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return response()->json($task);
    }

    public function destroyTask(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->json(status: 204);
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

        return response()->json($board->tasks()->get());
    }
}
