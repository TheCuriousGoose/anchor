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
    Tag,
    StickyNote,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import LabelManagerDialog from '@/components/LabelManagerDialog.vue';
import NoteEditor from '@/components/NoteEditor.vue';
import RenameBoardDialog from '@/components/RenameBoardDialog.vue';
import ShareBoardDialog from '@/components/ShareBoardDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
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
import { labelColorClasses } from '@/lib/labelColors';
import type { Board, Label, Note, Priority, Task } from '@/types/board';

const props = defineProps<{ board: Board; isAuthenticated: boolean }>();
const { board } = props;
const { t } = useI18n();

const priorityOptions: { value: Priority; label: string }[] = [
    { value: null, label: t('board.priority.none') },
    { value: 'low', label: t('board.priority.low') },
    { value: 'medium', label: t('board.priority.medium') },
    { value: 'high', label: t('board.priority.high') },
];

const newTask = ref('');
const newTaskPriority = ref<'none' | 'low' | 'medium' | 'high'>('none');
const search = ref('');
const filter = ref<'all' | 'open' | 'done'>('open');
const contentTab = ref<'tasks' | 'notes'>('tasks');
const renameOpen = ref(false);
const shareOpen = ref(false);
const labelsOpen = ref(false);
const saving = ref(false);
const lingeringCompletedTaskIds = ref(new Set<string>());
const completedTaskTimers = new Map<string, ReturnType<typeof setTimeout>>();

const canEdit = computed(() => board.role !== 'viewer');
const canReorder = computed(
    () =>
        canEdit.value &&
        ['all', 'open'].includes(filter.value) &&
        !search.value.trim(),
);
const openCount = computed(
    () => board.tasks.filter((task) => !task.completed).length,
);
const completedCount = computed(
    () => board.tasks.filter((task) => task.completed).length,
);
const visibleTasks = computed(() => board.tasks.filter(matchesFilter));
const reorderableTasks = computed<Task[]>({
    get: () =>
        filter.value === 'open'
            ? board.tasks.filter(
                (task) =>
                    !task.completed || lingeringCompletedTaskIds.value.has(task.id),
            )
            : board.tasks,
    set: (value) => {
        if (filter.value !== 'open') {
            board.tasks = value;

            return;
        }

        const openTasks = value.filter((task) => !task.completed);
        let openTaskIndex = 0;
        board.tasks = board.tasks.map((task) =>
            task.completed ? task : openTasks[openTaskIndex++],
        );
    },
});

function matchesFilter(task: Task): boolean {
    const query = search.value.trim().toLocaleLowerCase();
    const matchesSearch =
        !query || task.title.toLocaleLowerCase().includes(query);
    const matchesStatus =
        filter.value === 'all' ||
        (filter.value === 'done'
            ? task.completed
            : !task.completed || lingeringCompletedTaskIds.value.has(task.id));

    return matchesSearch && matchesStatus;
}

function clearCompletedTaskDelay(taskId: string): void {
    const timer = completedTaskTimers.get(taskId);

    if (timer) {
        clearTimeout(timer);
        completedTaskTimers.delete(taskId);
    }

    if (lingeringCompletedTaskIds.value.has(taskId)) {
        const nextIds = new Set(lingeringCompletedTaskIds.value);
        nextIds.delete(taskId);
        lingeringCompletedTaskIds.value = nextIds;
    }
}

function delayCompletedTaskRemoval(taskId: string): void {
    if (filter.value !== 'open') {
        return;
    }

    clearCompletedTaskDelay(taskId);
    lingeringCompletedTaskIds.value = new Set(
        lingeringCompletedTaskIds.value,
    ).add(taskId);

    completedTaskTimers.set(
        taskId,
        setTimeout(() => clearCompletedTaskDelay(taskId), 3000),
    );
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
        t('board.priority.none')
    );
}

function labelDotClass(label: Label): string {
    return labelColorClasses[label.color];
}

function hasLabel(task: Task, label: Label): boolean {
    return task.labels.some((item: Label) => item.id === label.id);
}

function toDateInputValue(dueDate: string | null): string {
    return dueDate ? dueDate.slice(0, 10) : '';
}

function isOverdue(task: Task): boolean {
    return (
        !task.completed &&
        Boolean(task.due_date) &&
        new Date(task.due_date as string).getTime() < Date.now()
    );
}

function formatDueDate(dueDate: string): string {
    return new Date(dueDate).toLocaleDateString();
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
                due_date: null,
                labels: [],
            });
        }

        newTask.value = '';
        newTaskPriority.value = 'none';
        refreshSidebarCounts();
    } catch {
        toast.error(t('board.toastAddTaskError'));
    }
}

async function toggleTask(task: Task): Promise<void> {
    task.completed = !task.completed;

    if (task.completed) {
        delayCompletedTaskRemoval(task.id);
    } else {
        clearCompletedTaskDelay(task.id);
    }

    if (props.isAuthenticated) {
        try {
            await request(`/tasks/${task.id}`, 'PATCH', {
                completed: task.completed,
            });
            refreshSidebarCounts();
        } catch {
            task.completed = !task.completed;
            clearCompletedTaskDelay(task.id);
            toast.error(t('board.toastUpdateTaskError'));
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
        toast.error(t('board.toastPriorityError'));
    }
}

async function updateTaskDueDate(task: Task, value: string): Promise<void> {
    const previous = task.due_date;
    const dueDate = value ? value : null;
    task.due_date = dueDate;

    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/tasks/${task.id}`, 'PATCH', { due_date: dueDate });
    } catch {
        task.due_date = previous;
        toast.error(t('board.toastDueDateError'));
    }
}

async function toggleTaskLabel(
    task: Task,
    label: Label,
    checked: boolean,
): Promise<void> {
    const previous = task.labels;
    const labelIds = checked
        ? [...previous.map((item) => item.id), label.id]
        : previous.map((item) => item.id).filter((id) => id !== label.id);

    task.labels = checked
        ? [...previous, label]
        : previous.filter((item) => item.id !== label.id);

    if (!props.isAuthenticated) {
        return;
    }

    try {
        const updated = await request<Task>(`/tasks/${task.id}`, 'PATCH', {
            label_ids: labelIds,
        });
        task.labels = updated.labels;
    } catch {
        task.labels = previous;
        toast.error(t('board.toastLabelError'));
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
        toast.error(t('board.toastDeleteTaskError'));
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
        toast.error(t('board.toastCreateNoteError'));
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
        toast.error(t('board.toastSaveNoteError'));
    }
}

async function deleteNote(note: Note): Promise<void> {
    try {
        if (props.isAuthenticated) {
            await request(`/notes/${note.id}`, 'DELETE');
        }

        board.notes = board.notes.filter((item) => item.id !== note.id);
    } catch {
        toast.error(t('board.toastDeleteNoteError'));
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

        toast.error(t('board.toastReorderError'));
    } finally {
        reorderSnapshot = null;
    }
}

async function deleteBoard(): Promise<void> {
    try {
        await request(`/boards/${board.id}`, 'DELETE');
        router.visit('/boards');
    } catch {
        toast.error(t('board.toastDeleteBoardError'));
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
                    {{ t('board.openCompleted', { open: openCount, completed: completedCount }) }}
                </p>
                <p v-if="!canEdit" class="mt-1 text-xs text-muted-foreground">
                    {{ t('board.viewOnly') }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Badge v-if="!board.isOwner" variant="secondary" class="capitalize">{{ board.role }}</Badge>
                <span v-if="saving" class="hidden text-xs text-muted-foreground sm:inline">{{ t('common.saving') }}</span>
                <DropdownMenu v-if="isAuthenticated && canEdit">
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" class="size-8" :title="t('board.boardMenu')">
                            <MoreHorizontal class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="renameOpen = true">
                            <Pencil class="size-4" /> {{ t('board.renameBoard') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="labelsOpen = true">
                            <Tag class="size-4" /> {{ t('board.manageLabels') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="board.isOwner" @click="shareOpen = true">
                            <Share2 class="size-4" /> {{ t('board.shareBoard') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="board.isOwner" variant="destructive" @click="deleteBoard">
                            <Trash2 class="size-4" /> {{ t('board.deleteBoard') }}
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
                {{ t(`board.tabs.${tab}`) }}
            </button>
        </div>

        <template v-if="contentTab === 'tasks'">
            <form v-if="canEdit" class="mb-7 flex items-center gap-2 border-b border-border pb-4"
                @submit.prevent="addTask">
                <Plus class="size-5 shrink-0 text-brand" />
                <Input v-model="newTask"
                    class="h-10 border-0 bg-transparent px-1 text-base shadow-none focus-visible:ring-0"
                    :placeholder="t('board.addTaskPlaceholder')" maxlength="255" />
                <Select v-model="newTaskPriority">
                    <SelectTrigger size="sm" class="w-28 text-xs">
                        <SelectValue :placeholder="t('board.priority.none')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">{{ t('board.priority.none') }}</SelectItem>
                        <SelectItem value="low">{{ t('board.priority.low') }}</SelectItem>
                        <SelectItem value="medium">{{ t('board.priority.medium') }}</SelectItem>
                        <SelectItem value="high">{{ t('board.priority.high') }}</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit" size="sm" class="bg-brand text-brand-foreground hover:bg-brand/90"
                    :disabled="!newTask.trim() || saving">{{ t('board.add') }}</Button>
            </form>

            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex items-center rounded-md border border-border p-0.5">
                    <button v-for="option in ['all', 'open', 'done'] as const" :key="option"
                        class="h-7 rounded px-3 text-xs font-medium capitalize transition-colors" :class="filter === option
                            ? 'bg-brand/10 text-brand'
                            : 'text-muted-foreground hover:text-foreground'
                            " @click="filter = option">
                        {{ t(`board.filters.${option}`) }}
                    </button>
                </div>
                <div class="relative sm:ml-auto sm:w-52">
                    <Search class="absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="h-8 bg-card pl-8 text-xs" :placeholder="t('board.filterPlaceholder')" />
                </div>
            </div>

            <div class="overflow-hidden rounded-md border border-border">
                <draggable v-model="reorderableTasks" item-key="id" tag="div" class="divide-y divide-border"
                    handle=".drag-handle" ghost-class="opacity-40" :disabled="!canReorder" @start="handleReorderStart"
                    @end="handleReorderEnd">
                    <template #item="{ element: task }">
                        <div v-show="matchesFilter(task)"
                            class="group flex min-h-14 flex-wrap items-center gap-3 px-4 py-2 transition-colors hover:bg-accent/50">
                            <GripVertical v-if="canReorder && !task.completed"
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

                            <span v-for="label in task.labels" :key="label.id"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full border border-border px-2 py-0.5 text-[11px] text-muted-foreground">
                                <span class="size-1.5 rounded-full" :class="labelDotClass(label)" />
                                {{ label.name }}
                            </span>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <button type="button" :disabled="!canEdit"
                                        class="flex size-7 shrink-0 items-center justify-center rounded text-muted-foreground opacity-100 hover:text-foreground disabled:pointer-events-none sm:opacity-0 sm:group-hover:opacity-100"
                                        :title="t('board.labelsButton')">
                                        <Tag class="size-3.5" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" class="w-56">
                                    <DropdownMenuCheckboxItem v-for="label in board.labels" :key="label.id"
                                        :model-value="hasLabel(task, label)"
                                        @select.prevent
                                        @update:model-value="
                                            (checked) => toggleTaskLabel(task, label, Boolean(checked))
                                            ">
                                        <span class="mr-2 inline-block size-2.5 rounded-full"
                                            :class="labelDotClass(label)" />
                                        {{ label.name }}
                                    </DropdownMenuCheckboxItem>
                                    <p v-if="board.labels.length === 0" class="px-2 py-1.5 text-xs text-muted-foreground">
                                        {{ t('board.noLabelsYet') }}
                                    </p>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @select.prevent @click="labelsOpen = true">
                                        {{ t('board.manageLabels') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <input v-if="canEdit" type="date" :value="toDateInputValue(task.due_date)"
                                class="w-34 shrink-0 rounded border-0 bg-transparent text-xs outline-none focus:ring-1 focus:ring-ring scheme-light dark:scheme-dark"
                                :class="isOverdue(task) ? 'font-medium text-destructive' : 'text-muted-foreground'"
                                :title="t('board.dueDate')"
                                @change="updateTaskDueDate(task, ($event.target as HTMLInputElement).value)" />
                            <span v-else-if="task.due_date" class="shrink-0 text-xs"
                                :class="isOverdue(task) ? 'font-medium text-destructive' : 'text-muted-foreground'">
                                {{ formatDueDate(task.due_date) }}
                            </span>

                            <Button v-if="canEdit" variant="ghost" size="icon"
                                class="size-8 text-muted-foreground opacity-100 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100"
                                :title="t('board.deleteTask')" @click="deleteTask(task)">
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
                        {{ t('board.emptyNothingHere') }}
                    </p>
                    <p v-if="search" class="mt-1 text-xs text-muted-foreground">
                        {{ t('board.emptyTryAnotherSearch') }}
                    </p>
                    <p v-else class="mt-1 text-xs text-muted-foreground">
                        {{ t('board.emptyAddTaskAbove') }}
                    </p>
                </div>
            </div>
        </template>

        <template v-else>
            <div class="mb-5 flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    {{ t('board.notesCount', board.notes.length) }}
                </p>
                <Button v-if="canEdit" size="sm" class="bg-brand text-brand-foreground hover:bg-brand/90"
                    @click="createNote">
                    <Plus class="size-4" /> {{ t('board.newNote') }}
                </Button>
            </div>

            <div v-if="board.notes.length === 0"
                class="flex flex-col items-center rounded-md border border-dashed border-border px-6 py-14 text-center">
                <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
                    <StickyNote class="size-5" />
                </div>
                <p class="text-sm font-medium text-foreground">{{ t('board.noNotesYetTitle') }}</p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ t('board.noNotesYetSubtitle') }}
                </p>
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2">
                <div v-for="note in board.notes" :key="note.id"
                    class="group relative rounded-md border border-border p-4">
                    <div class="mb-2 flex items-start gap-2">
                        <input :value="note.title" :readonly="!canEdit" :placeholder="t('board.untitled')"
                            class="min-w-0 flex-1 bg-transparent text-sm font-semibold text-foreground outline-none placeholder:text-muted-foreground/60"
                            @input="
                                note.title = (
                                    $event.target as HTMLInputElement
                                ).value
                                " @blur="saveNote(note)" />
                        <Button v-if="canEdit" variant="ghost" size="icon"
                            class="size-7 shrink-0 text-muted-foreground opacity-100 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100"
                            :title="t('board.deleteNote')" @click="deleteNote(note)">
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <NoteEditor v-model="note.body" :editable="canEdit" @save="saveNote(note)" />
                </div>
            </div>
        </template>
    </div>

    <RenameBoardDialog v-if="isAuthenticated" v-model:open="renameOpen" :board="board" />
    <ShareBoardDialog v-if="isAuthenticated" v-model:open="shareOpen" :board="board" />
    <LabelManagerDialog v-model:open="labelsOpen" :board="board" :is-authenticated="isAuthenticated" />
</template>
