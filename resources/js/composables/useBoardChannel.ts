import { router } from '@inertiajs/vue3';
import { usePresenceChannel } from '@laravel/echo-vue';
import type { ComputedRef, Ref } from 'vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import type {
    Actor,
    Board,
    Collaborator,
    EditingTarget,
    EditingWhisper,
    Label,
    Note,
    PresenceMember,
    Task,
} from '@/types/board';

/** How long an `editing` whisper stays live before we assume the sender went away. */
const EDITING_TTL_MS = 4000;

/** Collapses bursts of events into a single `sidebarBoards` refetch. */
const SIDEBAR_REFRESH_DEBOUNCE_MS = 400;

type BoardEventHandlers = {
    /** The board being viewed was deleted by its owner. */
    onBoardDeleted: (name: string) => void;
    /** Something worth surfacing happened; `messageKey` is an i18n key. */
    onActivity: (actor: Actor, messageKey: string) => void;
};

export type UseBoardChannelReturn = {
    /** Everyone currently viewing this board, including the local user. */
    members: Ref<PresenceMember[]>;
    /** Name of whoever is editing `target` right now, or null. */
    editingLabelFor: (target: EditingTarget) => string | null;
    /** Announce what the local user is editing; null clears it. */
    whisperEditing: (target: EditingTarget | null) => void;
    connected: ComputedRef<boolean>;
};

/**
 * Wires one board up to its presence channel: keeps the roster of who's here, applies
 * everyone else's changes to the local board object, and carries "X is editing…" whispers.
 *
 * The board object passed in is mutated in place — it's the same reactive object the page
 * renders from, which is what makes remote changes appear without a refetch.
 *
 * Events only ever arrive from *other* people: every broadcast is sent with `->toOthers()`
 * and Reverb never echoes whispers back to their sender.
 */
export function useBoardChannel(
    board: Board,
    enabled: boolean,
    me: { id: number; name: string },
    handlers: BoardEventHandlers,
): UseBoardChannelReturn {
    const members = ref<PresenceMember[]>([]);
    // Keyed by target; `id` is the sender, so switching targets replaces their old entry.
    const editing = ref(
        new Map<EditingTarget, { id: number; name: string; at: number }>(),
    );
    // Ticks so `editingLabelFor` re-evaluates as entries age out.
    const now = ref(Date.now());

    function editingLabelFor(target: EditingTarget): string | null {
        const entry = editing.value.get(target);

        if (!entry || now.value - entry.at > EDITING_TTL_MS) {
            return null;
        }

        return entry.name;
    }

    if (!enabled) {
        return {
            members,
            editingLabelFor,
            whisperEditing: () => {},
            connected: computed(() => false),
        };
    }

    // Subscribes immediately; releasing is refcounted, so sharing this channel with
    // another component on the same page is safe.
    const { channel } = usePresenceChannel(`boards.${board.id}`);
    const presence = channel();
    const connected = ref(false);

    const expiryTimer = window.setInterval(() => {
        now.value = Date.now();
    }, 1000);

    let sidebarTimer: number | undefined;

    /**
     * Board names and open-task counts live in the `sidebarBoards` shared prop, so rather
     * than duplicating that derivation client-side we just re-request the prop.
     */
    function refreshSidebar(): void {
        window.clearTimeout(sidebarTimer);
        sidebarTimer = window.setTimeout(() => {
            router.reload({ only: ['sidebarBoards'] });
        }, SIDEBAR_REFRESH_DEBOUNCE_MS);
    }

    function forgetEditingBy(
        userId: number,
    ): Map<EditingTarget, { id: number; name: string; at: number }> {
        const next = new Map(editing.value);

        for (const [target, entry] of next) {
            if (entry.id === userId) {
                next.delete(target);
            }
        }

        return next;
    }

    function sortTasks(): void {
        board.tasks.sort((a, b) => a.position - b.position);
    }

    function upsertTask(incoming: Task): void {
        const existing = board.tasks.find((task) => task.id === incoming.id);

        if (existing) {
            Object.assign(existing, incoming);
        } else {
            board.tasks.push(incoming);
        }

        sortTasks();
    }

    function upsertNote(incoming: Note): void {
        const existing = board.notes.find((note) => note.id === incoming.id);

        if (existing) {
            Object.assign(existing, incoming);
        } else {
            board.notes.unshift(incoming);
        }
    }

    function upsertLabel(incoming: Label): void {
        const existing = board.labels.find((label) => label.id === incoming.id);

        if (existing) {
            Object.assign(existing, incoming);
        } else {
            board.labels.push(incoming);
        }
    }

    /** Mirrors the notes table's `cascadeOnDelete`: dropping a page drops its subpages. */
    function noteIdsWithDescendants(noteId: string): Set<string> {
        const ids = new Set<string>([noteId]);

        for (let added = true; added;) {
            added = false;

            for (const note of board.notes) {
                if (
                    note.parent_id &&
                    ids.has(note.parent_id) &&
                    !ids.has(note.id)
                ) {
                    ids.add(note.id);
                    added = true;
                }
            }
        }

        return ids;
    }

    presence
        .here((users: PresenceMember[]) => {
            members.value = users;
            connected.value = true;
        })
        .joining((user: PresenceMember) => {
            if (!members.value.some((member) => member.id === user.id)) {
                members.value = [...members.value, user];
            }
        })
        .leaving((user: PresenceMember) => {
            members.value = members.value.filter(
                (member) => member.id !== user.id,
            );
            // Drop their editing hint now rather than waiting for it to expire.
            editing.value = forgetEditingBy(user.id);
        })
        .listenForWhisper('editing', (payload: EditingWhisper) => {
            const next = forgetEditingBy(payload.id);

            if (payload.target) {
                next.set(payload.target, {
                    id: payload.id,
                    name: payload.name,
                    at: Date.now(),
                });
            }

            editing.value = next;
        })
        .listen('.task.created', (payload: { actor: Actor; task: Task }) => {
            upsertTask(payload.task);
            refreshSidebar();
            handlers.onActivity(payload.actor, 'realtime.taskCreated');
        })
        .listen('.task.updated', (payload: { actor: Actor; task: Task }) => {
            upsertTask(payload.task);
            refreshSidebar();
        })
        .listen('.task.deleted', (payload: { actor: Actor; id: string }) => {
            board.tasks = board.tasks.filter((task) => task.id !== payload.id);
            refreshSidebar();
        })
        .listen('.tasks.reordered', (payload: { taskIds: string[] }) => {
            const order = new Map(
                payload.taskIds.map((id, index) => [id, index]),
            );

            for (const task of board.tasks) {
                task.position = order.get(task.id) ?? task.position;
            }

            sortTasks();
        })
        .listen('.note.created', (payload: { actor: Actor; note: Note }) => {
            upsertNote(payload.note);
        })
        .listen('.note.updated', (payload: { actor: Actor; note: Note }) => {
            upsertNote(payload.note);
        })
        .listen('.note.deleted', (payload: { actor: Actor; id: string }) => {
            const removed = noteIdsWithDescendants(payload.id);
            board.notes = board.notes.filter((note) => !removed.has(note.id));
        })
        .listen('.label.created', (payload: { actor: Actor; label: Label }) => {
            upsertLabel(payload.label);
        })
        .listen('.label.updated', (payload: { actor: Actor; label: Label }) => {
            upsertLabel(payload.label);

            // Labels are embedded in each task, so refresh the copies hanging off them.
            for (const task of board.tasks) {
                const attached = task.labels.find(
                    (label) => label.id === payload.label.id,
                );

                if (attached) {
                    Object.assign(attached, payload.label);
                }
            }
        })
        .listen('.label.deleted', (payload: { actor: Actor; id: string }) => {
            board.labels = board.labels.filter(
                (label) => label.id !== payload.id,
            );

            for (const task of board.tasks) {
                task.labels = task.labels.filter(
                    (label) => label.id !== payload.id,
                );
            }
        })
        .listen(
            '.board.updated',
            (payload: { actor: Actor; name: string; icon: string }) => {
                board.name = payload.name;
                board.icon = payload.icon;
                refreshSidebar();
                handlers.onActivity(payload.actor, 'realtime.boardRenamed');
            },
        )
        .listen('.board.deleted', (payload: { actor: Actor; name: string }) => {
            handlers.onBoardDeleted(payload.name);
        })
        .listen(
            '.board.collaborators.changed',
            (payload: { collaborators: Collaborator[] }) => {
                board.collaborators = payload.collaborators;
            },
        );

    let heartbeatTimer: number | undefined;
    let currentTarget: EditingTarget | null = null;

    function sendEditing(target: EditingTarget | null): void {
        presence.whisper('editing', {
            id: me.id,
            name: me.name,
            target,
        } satisfies EditingWhisper);
    }

    /**
     * Announces what the local user is editing; null clears it.
     *
     * Idempotent, so callers can fire it on every keystroke without flooding the socket.
     * While a target is set it re-sends on a heartbeat, otherwise the hint would expire on
     * watchers mid-edit — someone typing a long note is still "editing" it.
     */
    function whisperEditing(target: EditingTarget | null): void {
        if (target === currentTarget) {
            return;
        }

        currentTarget = target;
        window.clearInterval(heartbeatTimer);
        sendEditing(target);

        if (target !== null) {
            heartbeatTimer = window.setInterval(
                () => sendEditing(target),
                EDITING_TTL_MS / 2,
            );
        }
    }

    onBeforeUnmount(() => {
        window.clearInterval(expiryTimer);
        window.clearInterval(heartbeatTimer);
        window.clearTimeout(sidebarTimer);

        // Tell everyone else the hint can go now, rather than making them wait it out.
        sendEditing(null);
    });

    return {
        members,
        editingLabelFor,
        whisperEditing,
        connected: computed(() => connected.value),
    };
}
