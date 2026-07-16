<?php

namespace Tests\Feature;

use App\Events\BoardAccessChanged;
use App\Events\BoardAccessGranted;
use App\Events\BoardAccessRevoked;
use App\Events\BoardCollaboratorsChanged;
use App\Events\BoardDeleted;
use App\Events\BoardListChanged;
use App\Events\BoardUpdated;
use App\Events\LabelCreated;
use App\Events\LabelDeleted;
use App\Events\LabelUpdated;
use App\Events\NoteCreated;
use App\Events\NoteDeleted;
use App\Events\NoteUpdated;
use App\Events\TaskCreated;
use App\Events\TaskDeleted;
use App\Events\TasksReordered;
use App\Events\TaskUpdated;
use App\Models\Board;
use App\Models\User;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BoardBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_task_broadcasts_it_on_the_board_channel()
    {
        Event::fake([TaskCreated::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('tasks.store', $board), ['title' => 'Ship it'])
            ->assertCreated();

        Event::assertDispatched(TaskCreated::class, function (TaskCreated $event) use ($board): bool {
            $payload = $event->broadcastWith();

            $this->assertEquals(
                [new PresenceChannel("boards.{$board->id}")],
                $event->broadcastOn(),
            );
            $this->assertSame('task.created', $event->broadcastAs());
            $this->assertSame('Ship it', $payload['task']['title']);
            // Labels ride along so the receiver can render the row without a follow-up fetch.
            $this->assertArrayHasKey('labels', $payload['task']);

            // A newly created model only holds the attributes that were explicitly set, so
            // without a refresh these would be missing entirely and the receiving client
            // would build a Task that doesn't match its own type.
            $this->assertFalse($payload['task']['completed']);
            $this->assertNull($payload['task']['description']);
            $this->assertNull($payload['task']['due_date']);

            return true;
        });
    }

    public function test_task_broadcast_payload_matches_the_http_response()
    {
        Event::fake([TaskCreated::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $response = $this->actingAs($owner)
            ->postJson(route('tasks.store', $board), ['title' => 'Ship it'])
            ->assertCreated();

        Event::assertDispatched(TaskCreated::class, function (TaskCreated $event) use ($response): bool {
            // Collaborators must end up with exactly what the acting client got back,
            // otherwise their optimistic state and the live state drift apart.
            $this->assertSame($response->json(), $event->broadcastWith()['task']);

            return true;
        });
    }

    public function test_broadcast_carries_the_acting_user_so_the_ui_can_attribute_changes()
    {
        Event::fake([TaskCreated::class]);

        $owner = User::factory()->create(['name' => 'Ada Lovelace']);
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('tasks.store', $board), ['title' => 'Ship it']);

        Event::assertDispatched(TaskCreated::class, function (TaskCreated $event) use ($owner): bool {
            $this->assertSame(
                ['id' => $owner->id, 'name' => 'Ada Lovelace'],
                $event->broadcastWith()['actor'],
            );

            return true;
        });
    }

    public function test_the_socket_id_header_excludes_the_acting_tab_from_its_own_broadcast()
    {
        Event::fake([TaskCreated::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->withHeader('X-Socket-ID', '1234.5678')
            ->postJson(route('tasks.store', $board), ['title' => 'Ship it']);

        // `->toOthers()` works by stamping the caller's socket onto the event; without the
        // header the acting tab would receive its own change back.
        Event::assertDispatched(
            TaskCreated::class,
            fn (TaskCreated $event): bool => $event->socket === '1234.5678',
        );
    }

    public function test_updating_and_deleting_a_task_broadcasts()
    {
        Event::fake([TaskUpdated::class, TaskDeleted::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $task = $board->tasks()->create(['title' => 'Ship it', 'position' => 0]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.update', $task), ['completed' => true])
            ->assertOk();

        Event::assertDispatched(
            TaskUpdated::class,
            fn (TaskUpdated $event): bool => $event->broadcastWith()['task']['completed'] === true,
        );

        $this->actingAs($owner)
            ->deleteJson(route('tasks.destroy', $task))
            ->assertNoContent();

        Event::assertDispatched(TaskDeleted::class, function (TaskDeleted $event) use ($task, $board): bool {
            $this->assertSame($task->id, $event->broadcastWith()['id']);
            $this->assertSame($board->id, $event->boardId);

            return true;
        });
    }

    public function test_reordering_broadcasts_the_new_order()
    {
        Event::fake([TasksReordered::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $first = $board->tasks()->create(['title' => 'First', 'position' => 0]);
        $second = $board->tasks()->create(['title' => 'Second', 'position' => 1]);

        $this->actingAs($owner)
            ->patchJson(route('tasks.reorder', $board), [
                'taskIds' => [$second->id, $first->id],
            ])
            ->assertOk();

        Event::assertDispatched(
            TasksReordered::class,
            fn (TasksReordered $event): bool => $event->broadcastWith()['taskIds'] === [$second->id, $first->id],
        );
    }

    public function test_note_mutations_broadcast()
    {
        Event::fake([NoteCreated::class, NoteUpdated::class, NoteDeleted::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('notes.store', $board), ['title' => 'Spec'])
            ->assertCreated();

        Event::assertDispatched(NoteCreated::class);

        $note = $board->notes()->first();

        $this->actingAs($owner)
            ->patchJson(route('notes.update', $note), ['title' => 'Spec v2'])
            ->assertOk();

        Event::assertDispatched(
            NoteUpdated::class,
            fn (NoteUpdated $event): bool => $event->broadcastWith()['note']['title'] === 'Spec v2',
        );

        $this->actingAs($owner)
            ->deleteJson(route('notes.destroy', $note))
            ->assertNoContent();

        Event::assertDispatched(
            NoteDeleted::class,
            fn (NoteDeleted $event): bool => $event->broadcastWith()['id'] === $note->id,
        );
    }

    public function test_label_mutations_broadcast()
    {
        Event::fake([LabelCreated::class, LabelUpdated::class, LabelDeleted::class]);

        $owner = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('labels.store', $board), ['name' => 'Bug', 'color' => 'red'])
            ->assertCreated();

        Event::assertDispatched(LabelCreated::class);

        $label = $board->labels()->first();

        $this->actingAs($owner)
            ->patchJson(route('labels.update', $label), ['name' => 'Defect', 'color' => 'amber'])
            ->assertOk();

        Event::assertDispatched(
            LabelUpdated::class,
            fn (LabelUpdated $event): bool => $event->broadcastWith()['label']['name'] === 'Defect',
        );

        $this->actingAs($owner)
            ->deleteJson(route('labels.destroy', $label))
            ->assertNoContent();

        Event::assertDispatched(LabelDeleted::class);
    }

    public function test_renaming_a_board_broadcasts_to_viewers_and_to_every_members_sidebar()
    {
        Event::fake([BoardUpdated::class, BoardListChanged::class]);

        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->actingAs($owner)
            ->patchJson(route('boards.update', $board), ['name' => 'Q3 Roadmap'])
            ->assertOk();

        Event::assertDispatched(
            BoardUpdated::class,
            fn (BoardUpdated $event): bool => $event->broadcastWith()['name'] === 'Q3 Roadmap',
        );

        // The sidebar shows board names, so a rename has to reach collaborators who
        // aren't currently looking at the board.
        Event::assertDispatched(BoardListChanged::class, function (BoardListChanged $event) use ($owner, $collaborator): bool {
            $this->assertEqualsCanonicalizing([$owner->id, $collaborator->id], $event->userIds);
            $this->assertEqualsCanonicalizing(
                [
                    new PrivateChannel("App.Models.User.{$owner->id}"),
                    new PrivateChannel("App.Models.User.{$collaborator->id}"),
                ],
                $event->broadcastOn(),
            );

            return true;
        });
    }

    public function test_deleting_a_board_broadcasts_before_the_members_are_gone()
    {
        Event::fake([BoardDeleted::class, BoardListChanged::class]);

        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'viewer']);

        $this->actingAs($owner)
            ->deleteJson(route('boards.destroy', $board))
            ->assertNoContent();

        Event::assertDispatched(
            BoardDeleted::class,
            fn (BoardDeleted $event): bool => $event->broadcastWith()['name'] === 'Roadmap',
        );

        // The pivot rows are gone by now, so this only works because the controller
        // captured the members before deleting.
        Event::assertDispatched(
            BoardListChanged::class,
            fn (BoardListChanged $event): bool => in_array($collaborator->id, $event->userIds, true),
        );
    }

    public function test_sharing_a_board_notifies_the_new_collaborator_directly()
    {
        Event::fake([BoardAccessGranted::class, BoardCollaboratorsChanged::class]);

        $owner = User::factory()->create(['name' => 'Ada']);
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);

        $this->actingAs($owner)
            ->postJson(route('boards.share.store', $board), [
                'email' => $collaborator->email,
                'role' => 'editor',
            ])
            ->assertCreated();

        // They can't be on the board channel yet, so this has to go to their own channel.
        Event::assertDispatched(BoardAccessGranted::class, function (BoardAccessGranted $event) use ($collaborator): bool {
            $payload = $event->broadcastWith();

            $this->assertEquals(
                [new PrivateChannel("App.Models.User.{$collaborator->id}")],
                $event->broadcastOn(),
            );
            $this->assertSame('Roadmap', $payload['board']['name']);
            $this->assertSame('editor', $payload['role']);
            $this->assertSame('Ada', $payload['sharedBy']);

            return true;
        });

        Event::assertDispatched(BoardCollaboratorsChanged::class, function (BoardCollaboratorsChanged $event) use ($collaborator): bool {
            $collaborators = $event->broadcastWith()['collaborators'];

            $this->assertCount(1, $collaborators);
            $this->assertSame($collaborator->id, $collaborators[0]['id']);
            $this->assertSame('editor', $collaborators[0]['role']);

            return true;
        });
    }

    public function test_changing_a_role_notifies_that_collaborator()
    {
        Event::fake([BoardAccessChanged::class]);

        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->actingAs($owner)
            ->patchJson(route('boards.share.update', [$board, $collaborator]), ['role' => 'viewer'])
            ->assertOk();

        Event::assertDispatched(BoardAccessChanged::class, function (BoardAccessChanged $event) use ($board, $collaborator): bool {
            $this->assertSame($collaborator->id, $event->userId);
            $this->assertSame(['boardId' => $board->id, 'role' => 'viewer'], $event->broadcastWith());

            return true;
        });
    }

    public function test_revoking_access_notifies_the_removed_collaborator()
    {
        Event::fake([BoardAccessRevoked::class, BoardCollaboratorsChanged::class]);

        $owner = User::factory()->create();
        $collaborator = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($collaborator->id, ['role' => 'editor']);

        $this->actingAs($owner)
            ->deleteJson(route('boards.share.destroy', [$board, $collaborator]))
            ->assertNoContent();

        Event::assertDispatched(BoardAccessRevoked::class, function (BoardAccessRevoked $event) use ($board, $collaborator): bool {
            $this->assertSame($collaborator->id, $event->userId);
            $this->assertSame(
                ['boardId' => $board->id, 'boardName' => 'Roadmap'],
                $event->broadcastWith(),
            );

            return true;
        });

        // The roster the owner sees should be empty again.
        Event::assertDispatched(
            BoardCollaboratorsChanged::class,
            fn (BoardCollaboratorsChanged $event): bool => $event->broadcastWith()['collaborators'] === [],
        );
    }

    public function test_a_viewer_cannot_trigger_broadcasts_they_are_not_allowed_to_make()
    {
        Event::fake([TaskCreated::class]);

        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $this->actingAs($viewer)
            ->postJson(route('tasks.store', $board), ['title' => 'Nope'])
            ->assertNotFound();

        Event::assertNotDispatched(TaskCreated::class);
    }

    public function test_member_ids_covers_the_owner_and_every_collaborator()
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();

        /** @var Board $board */
        $board = $owner->boards()->create(['name' => 'Roadmap']);
        $board->collaborators()->attach($editor->id, ['role' => 'editor']);
        $board->collaborators()->attach($viewer->id, ['role' => 'viewer']);

        $this->assertEqualsCanonicalizing(
            [$owner->id, $editor->id, $viewer->id],
            $board->memberIds(),
        );
    }
}
