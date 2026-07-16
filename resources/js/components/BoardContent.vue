<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    GripVertical,
    Inbox,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Share2,
    StickyNote,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import RenameBoardDialog from '@/components/RenameBoardDialog.vue';
import ShareBoardDialog from '@/components/ShareBoardDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { request as apiRequest } from '@/lib/boardApi';
import type { Board, Note, Priority, Task } from '@/types/board';

const props = defineProps<{ board: Board; isAuthenticated: boolean }>();
const { board } = props;

const priorityOptions: { value: Priority; label: string }[] = [
    { value: null, label: 'No priority' },
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];

const newTask = ref('');
const newTaskPriority = ref<'none' | 'low' | 'medium' | 'high'>('none');
const search = ref('');
const filter = ref<'all' | 'open' | 'done'>('open');
const contentTab = ref<'tasks' | 'notes'>('tasks');
const renameOpen = ref(false);
const shareOpen = ref(false);
const saving = ref(false);

const canEdit = computed(() => board.role !== 'viewer');
const canReorder = computed(
    () => canEdit.value && filter.value === 'all' && !search.value.trim(),
);
const openCount = computed(
    () => board.tasks.filter((task) => !task.completed).length,
);
const completedCount = computed(
    () => board.tasks.filter((task) => task.completed).length,
);
const visibleTasks = computed(() => board.tasks.filter(matchesFilter));
const orderedTasks = computed<Task[]>({
    get: () => board.tasks,
    set: (value) => {
        board.tasks = value;
    },
});

function matchesFilter(task: Task): boolean {
    const query = search.value.trim().toLocaleLowerCase();
    const matchesSearch =
        !query || task.title.toLocaleLowerCase().includes(query);
    const matchesStatus =
        filter.value === 'all' ||
        (filter.value === 'done' ? task.completed : !task.completed);

    return matchesSearch && matchesStatus;
}

const priorityDotClasses: Record<'high' | 'medium' | 'low', string> = {
    high: 'bg-destructive',
    medium: 'bg-amber-500',
    low: 'bg-sky-500',
};

function priorityDotClass(priority: Priority): string {
    return priority
        ? priorityDotClasses[priority]
        : 'ring-1 ring-inset ring-border';
}

function priorityLabel(priority: Priority): string {
    return (
        priorityOptions.find((option) => option.value === priority)?.label ??
        'No priority'
    );
}

async function request<T>(
    url: string,
    method: string,
    body?: object,
): Promise<T> {
    saving.value = true;

    try {
        return await apiRequest<T>(url, method, body);
    } finally {
        saving.value = false;
    }
}

function refreshSidebarCounts(): void {
    if (props.isAuthenticated) {
        router.reload({ only: ['sidebarBoards'] });
    }
}

async function addTask(): Promise<void> {
    const title = newTask.value.trim();

    if (!title) {
        return;
    }

    const priority =
        newTaskPriority.value === 'none' ? null : newTaskPriority.value;

    try {
        if (props.isAuthenticated) {
            const task = await request<Task>(
                `/boards/${board.id}/tasks`,
                'POST',
                { title, priority },
            );
            board.tasks.push(task);
        } else {
            board.tasks.push({
                id: crypto.randomUUID(),
                title,
                completed: false,
                position: board.tasks.length,
                priority,
            });
        }

        newTask.value = '';
        newTaskPriority.value = 'none';
        refreshSidebarCounts();
    } catch {
        toast.error('Could not add the task. Try again.');
    }
}

async function toggleTask(task: Task): Promise<void> {
    task.completed = !task.completed;

    if (props.isAuthenticated) {
        try {
            await request(`/tasks/${task.id}`, 'PATCH', {
                completed: task.completed,
            });
            refreshSidebarCounts();
        } catch {
            task.completed = !task.completed;
            toast.error('Could not update the task. Try again.');
        }
    }
}

async function updateTaskPriority(
    task: Task,
    priority: Priority,
): Promise<void> {
    const previous = task.priority;
    task.priority = priority;

    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/tasks/${task.id}`, 'PATCH', { priority });
    } catch {
        task.priority = previous;
        toast.error('Could not update priority. Try again.');
    }
}

async function deleteTask(task: Task): Promise<void> {
    try {
        if (props.isAuthenticated) {
            await request(`/tasks/${task.id}`, 'DELETE');
        }

        board.tasks = board.tasks.filter((item) => item.id !== task.id);
        refreshSidebarCounts();
    } catch {
        toast.error('Could not delete the task. Try again.');
    }
}

async function createNote(): Promise<void> {
    try {
        if (props.isAuthenticated) {
            const note = await request<Note>(
                `/boards/${board.id}/notes`,
                'POST',
                {},
            );
            board.notes.unshift(note);
        } else {
            const now = new Date().toISOString();
            board.notes.unshift({
                id: crypto.randomUUID(),
                title: '',
                body: '',
                created_at: now,
                updated_at: now,
            });
        }
    } catch {
        toast.error('Could not create the note. Try again.');
    }
}

async function saveNote(note: Note): Promise<void> {
    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/notes/${note.id}`, 'PATCH', {
            title: note.title,
            body: note.body,
        });
    } catch {
        toast.error('Could not save the note. Try again.');
    }
}

async function deleteNote(note: Note): Promise<void> {
    try {
        if (props.isAuthenticated) {
            await request(`/notes/${note.id}`, 'DELETE');
        }

        board.notes = board.notes.filter((item) => item.id !== note.id);
    } catch {
        toast.error('Could not delete the note. Try again.');
    }
}

let reorderSnapshot: Task[] | null = null;

function handleReorderStart(): void {
    reorderSnapshot = [...board.tasks];
}

async function handleReorderEnd(): Promise<void> {
    board.tasks.forEach((task, index) => {
        task.position = index;
    });

    if (!props.isAuthenticated) {
        reorderSnapshot = null;

        return;
    }

    try {
        await request(`/boards/${board.id}/tasks/reorder`, 'PATCH', {
            taskIds: board.tasks.map((task) => task.id),
        });
    } catch {
        if (reorderSnapshot) {
            board.tasks = reorderSnapshot;
        }

        toast.error('Could not save the new order. Try again.');
    } finally {
        reorderSnapshot = null;
    }
}

async function deleteBoard(): Promise<void> {
    try {
        await request(`/boards/${board.id}`, 'DELETE');
        router.visit('/boards');
    } catch {
        toast.error('Could not delete the board. Try again.');
    }
}
</script>

<template>
    <div class="mx-auto w-full max-w-4xl px-5 py-10 sm:px-10 md:py-16">
        <div class="mb-10 flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="truncate font-serif text-3xl font-semibold tracking-normal text-foreground sm:text-4xl">
                    {{ board.name }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ openCount }} open · {{ completedCount }} completed
                </p>
                <p v-if="!canEdit" class="mt-1 text-xs text-muted-foreground">
                    You have view-only access to this board.
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Badge v-if="!board.isOwner" variant="secondary" class="capitalize">{{ board.role }}</Badge>
                <span v-if="saving" class="hidden text-xs text-muted-foreground sm:inline">Saving…</span>
                <DropdownMenu v-if="isAuthenticated && canEdit">
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="size-8" title="Board menu">
                            <MoreHorizontal class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="renameOpen = true">
                            <Pencil class="size-4" /> Rename board
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="board.isOwner" @click="shareOpen = true">
                            <Share2 class="size-4" /> Share board
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="board.isOwner" variant="destructive" @click="deleteBoard">
                            <Trash2 class="size-4" /> Delete board
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div class="mb-7 flex w-fit items-center rounded-md border border-border p-0.5">
            <button v-for="tab in ['tasks', 'notes'] as const" :key="tab"
                class="h-7 rounded px-3 text-xs font-medium capitalize transition-colors" :class="contentTab === tab
                    ? 'bg-brand/10 text-brand'
                    : 'text-muted-foreground hover:text-foreground'
                    " @click="contentTab = tab">
                {{ tab }}
            </button>
        </div>

        <template v-if="contentTab === 'tasks'">
            <form v-if="canEdit" class="mb-7 flex items-center gap-2 border-b border-border pb-4"
                @submit.prevent="addTask">
                <Plus class="size-5 shrink-0 text-brand" />
                <Input v-model="newTask"
                    class="h-10 border-0 bg-transparent px-1 text-base shadow-none focus-visible:ring-0"
                    placeholder="Add a task…" maxlength="255" />
                <Select v-model="newTaskPriority">
                    <SelectTrigger size="sm" class="w-28 text-xs">
                        <SelectValue placeholder="Priority" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">No priority</SelectItem>
                        <SelectItem value="low">Low</SelectItem>
                        <SelectItem value="medium">Medium</SelectItem>
                        <SelectItem value="high">High</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit" size="sm" class="bg-brand text-brand-foreground hover:bg-brand/90"
                    :disabled="!newTask.trim() || saving">Add</Button>
            </form>

            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center rounded-md border border-border p-0.5">
                    <button v-for="option in ['all', 'open', 'done'] as const" :key="option"
                        class="h-7 rounded px-3 text-xs font-medium capitalize transition-colors" :class="filter === option
                            ? 'bg-brand/10 text-brand'
                            : 'text-muted-foreground hover:text-foreground'
                            " @click="filter = option">
                        {{ option }}
                    </button>
                </div>
                <div class="relative sm:ml-auto sm:w-52">
                    <Search class="absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="h-8 bg-card pl-8 text-xs" placeholder="Filter tasks" />
                </div>
            </div>

            <div class="overflow-hidden rounded-md border border-border">
                <draggable v-model="orderedTasks" item-key="id" tag="div" class="divide-y divide-border"
                    handle=".drag-handle" ghost-class="opacity-40" :disabled="!canReorder" @start="handleReorderStart"
                    @end="handleReorderEnd">
                    <template #item="{ element: task }">
                        <div v-show="matchesFilter(task)"
                            class="group flex min-h-14 items-center gap-3 px-4 transition-colors hover:bg-accent/50">
                            <GripVertical v-if="canReorder"
                                class="drag-handle size-4 shrink-0 cursor-grab text-muted-foreground/40 hover:text-muted-foreground" />
                            <Checkbox :model-value="task.completed" :disabled="!canEdit"
                                :aria-label="`Mark ${task.title} complete`" @update:model-value="toggleTask(task)" />
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button type="button"
                                        class="size-2.5 shrink-0 rounded-full disabled:cursor-not-allowed"
                                        :class="priorityDotClass(task.priority)" :disabled="!canEdit"
                                        :title="priorityLabel(task.priority)" />
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start">
                                    <DropdownMenuItem v-for="option in priorityOptions" :key="option.value ?? 'none'"
                                        @click="
                                            updateTaskPriority(
                                                task,
                                                option.value,
                                            )
                                            ">
                                        <span class="size-2.5 rounded-full" :class="priorityDotClass(option.value)
                                            " />
                                        {{ option.label }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                            <span class="min-w-0 flex-1 text-sm" :class="task.completed
                                ? 'text-muted-foreground line-through'
                                : 'text-foreground'
                                ">{{ task.title }}</span>
                            <Button v-if="canEdit" variant="ghost" size="icon"
                                class="size-8 text-muted-foreground opacity-100 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100"
                                title="Delete task" @click="deleteTask(task)">
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </template>
                </draggable>
                <div v-if="visibleTasks.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                    <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
                        <CheckCircle2 v-if="filter === 'done'" class="size-5" />
                        <Inbox v-else class="size-5" />
                    </div>
                    <p class="text-sm font-medium text-foreground">
                        Nothing here
                    </p>
                    <p v-if="search" class="mt-1 text-xs text-muted-foreground">
                        Try another search.
                    </p>
                    <p v-else class="mt-1 text-xs text-muted-foreground">
                        Add a task above.
                    </p>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="mb-5 flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ board.notes.length }} note{{
                        board.notes.length === 1 ? '' : 's'
                    }}
                </p>
                <Button v-if="canEdit" size="sm" class="bg-brand text-brand-foreground hover:bg-brand/90"
                    @click="createNote">
                    <Plus class="size-4" /> New note
                </Button>
            </div>

            <div v-if="board.notes.length === 0"
                class="flex flex-col items-center rounded-md border border-dashed border-border px-6 py-14 text-center">
                <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
                    <StickyNote class="size-5" />
                </div>
                <p class="text-sm font-medium text-foreground">No notes yet</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Jot down anything worth keeping.
                </p>
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2">
                <div v-for="note in board.notes" :key="note.id"
                    class="group relative rounded-md border border-border p-4">
                    <div class="mb-2 flex items-start gap-2">
                        <input :value="note.title" :readonly="!canEdit" placeholder="Untitled"
                            class="min-w-0 flex-1 bg-transparent text-sm font-semibold text-foreground outline-none placeholder:text-muted-foreground/60"
                            @input="
                                note.title = (
                                    $event.target as HTMLInputElement
                                ).value
                                " @blur="saveNote(note)" />
                        <Button v-if="canEdit" variant="ghost" size="icon"
                            class="size-7 shrink-0 text-muted-foreground opacity-100 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100"
                            title="Delete note" @click="deleteNote(note)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <textarea :value="note.body" :readonly="!canEdit" rows="4" placeholder="Write something…"
                        class="w-full resize-none bg-transparent text-sm text-foreground outline-none placeholder:text-muted-foreground/60"
                        @input="
                            note.body = (
                                $event.target as HTMLTextAreaElement
                            ).value
                            " @blur="saveNote(note)" />
                </div>
            </div>
        </template>
    </div>

    <RenameBoardDialog v-if="isAuthenticated" v-model:open="renameOpen" :board="board" />
    <ShareBoardDialog v-if="isAuthenticated" v-model:open="shareOpen" :board="board" />
</template>
