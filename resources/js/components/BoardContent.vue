<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    CalendarDays,
    GripVertical,
    Inbox,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Share2,
    Tag,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import LabelManagerDialog from '@/components/LabelManagerDialog.vue';
import NotesPanel from '@/components/NotesPanel.vue';
import PresenceAvatars from '@/components/PresenceAvatars.vue';
import RenameBoardDialog from '@/components/RenameBoardDialog.vue';
import ShareBoardDialog from '@/components/ShareBoardDialog.vue';
import TaskDetailDialog from '@/components/TaskDetailDialog.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
import { useBoardChannel } from '@/composables/useBoardChannel';
import { request as apiRequest } from '@/lib/boardApi';
import { labelColorClasses } from '@/lib/labelColors';
import type { Actor, Board, Label, Priority, Task } from '@/types/board';

const props = defineProps<{ board: Board; isAuthenticated: boolean }>();
const { board } = props;
const { t } = useI18n();
const page = usePage();

const currentUser = computed(() => page.props.auth?.user);

// Real-time collaboration. Guests own a single local board with nobody to sync to, so the
// channel stays dormant for them.
const { members, editingLabelFor, whisperEditing } = useBoardChannel(
    board,
    props.isAuthenticated,
    {
        id: currentUser.value?.id ?? 0,
        name: currentUser.value?.name ?? '',
    },
    {
        onBoardDeleted: (name: string): void => {
            toast.info(t('realtime.boardDeletedByOwner', { name }));
            router.visit('/boards');
        },
        onActivity: (actor: Actor, messageKey: string): void => {
            if (actor) {
                toast.message(t(messageKey, { name: actor.name }));
            }
        },
    },
);

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
const deleteOpen = ref(false);
const deleteTaskOpen = ref(false);
const deleteTaskTarget = ref<Task | null>(null);
const taskDetailOpen = ref(false);
const taskDetailTask = ref<Task | null>(null);
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
                      !task.completed ||
                      lingeringCompletedTaskIds.value.has(task.id),
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

function openTaskDetail(task: Task): void {
    taskDetailTask.value = task;
    taskDetailOpen.value = true;
}

// Having the detail dialog open is what "editing a task" means here, so the hint tracks
// the dialog rather than individual keystrokes.
watch([taskDetailOpen, taskDetailTask], ([open, task]) => {
    whisperEditing(open && task ? `task:${task.id}` : null);
});

function taskEditingLabel(task: Task): string | null {
    return editingLabelFor(`task:${task.id}`);
}

function hasLabel(task: Task, label: Label): boolean {
    return task.labels.some((item: Label) => item.id === label.id);
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
                description: null,
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
        deleteTaskOpen.value = false;
        deleteTaskTarget.value = null;
    } catch {
        toast.error(t('board.toastDeleteTaskError'));
    }
}

function confirmDeleteTask(task: Task): void {
    deleteTaskTarget.value = task;
    deleteTaskOpen.value = true;
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
                <h1
                    class="truncate font-serif text-3xl font-semibold tracking-normal text-foreground sm:text-4xl"
                >
                    {{ board.name }}
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        t('board.openCompleted', {
                            open: openCount,
                            completed: completedCount,
                        })
                    }}
                </p>
                <p v-if="!canEdit" class="mt-1 text-xs text-muted-foreground">
                    {{ t('board.viewOnly') }}
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <PresenceAvatars
                    v-if="isAuthenticated"
                    :members="members"
                    :current-user-id="currentUser?.id ?? 0"
                />
                <Badge
                    v-if="!board.isOwner"
                    variant="secondary"
                    class="capitalize"
                    >{{ board.role }}</Badge
                >
                <span
                    v-if="saving"
                    class="hidden text-xs text-muted-foreground sm:inline"
                    >{{ t('common.saving') }}</span
                >
                <DropdownMenu v-if="isAuthenticated && canEdit">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8"
                            :title="t('board.boardMenu')"
                        >
                            <MoreHorizontal class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="renameOpen = true">
                            <Pencil class="size-4" />
                            {{ t('board.renameBoard') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="labelsOpen = true">
                            <Tag class="size-4" /> {{ t('board.manageLabels') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="board.isOwner"
                            @click="shareOpen = true"
                        >
                            <Share2 class="size-4" />
                            {{ t('board.shareBoard') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            v-if="board.isOwner"
                            variant="destructive"
                            @click="deleteOpen = true"
                        >
                            <Trash2 class="size-4" />
                            {{ t('board.deleteBoard') }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div
            class="mb-7 flex w-fit items-center rounded-md border border-border p-0.5"
        >
            <button
                v-for="tab in ['tasks', 'notes'] as const"
                :key="tab"
                class="h-7 rounded px-3 text-xs font-medium capitalize transition-colors"
                :class="
                    contentTab === tab
                        ? 'bg-brand/10 text-brand'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="contentTab = tab"
            >
                {{ t(`board.tabs.${tab}`) }}
            </button>
        </div>

        <template v-if="contentTab === 'tasks'">
            <form
                v-if="canEdit"
                class="mb-7 flex items-center gap-2 border-b border-border pb-4"
                @submit.prevent="addTask"
            >
                <Plus class="size-5 shrink-0 text-brand" />
                <Input
                    v-model="newTask"
                    class="h-10 border-0 bg-transparent px-1 text-base shadow-none focus-visible:ring-0"
                    :placeholder="t('board.addTaskPlaceholder')"
                    maxlength="255"
                />
                <Select v-model="newTaskPriority">
                    <SelectTrigger size="sm" class="w-28 text-xs">
                        <SelectValue :placeholder="t('board.priority.none')" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">{{
                            t('board.priority.none')
                        }}</SelectItem>
                        <SelectItem value="low">{{
                            t('board.priority.low')
                        }}</SelectItem>
                        <SelectItem value="medium">{{
                            t('board.priority.medium')
                        }}</SelectItem>
                        <SelectItem value="high">{{
                            t('board.priority.high')
                        }}</SelectItem>
                    </SelectContent>
                </Select>
                <Button
                    type="submit"
                    size="sm"
                    class="bg-brand text-brand-foreground hover:bg-brand/90"
                    :disabled="!newTask.trim() || saving"
                    >{{ t('board.add') }}</Button
                >
            </form>

            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div
                    class="flex items-center rounded-md border border-border p-0.5"
                >
                    <button
                        v-for="option in ['all', 'open', 'done'] as const"
                        :key="option"
                        class="h-7 rounded px-3 text-xs font-medium capitalize transition-colors"
                        :class="
                            filter === option
                                ? 'bg-brand/10 text-brand'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="filter = option"
                    >
                        {{ t(`board.filters.${option}`) }}
                    </button>
                </div>
                <div class="relative sm:ml-auto sm:w-52">
                    <Search
                        class="absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        class="h-8 bg-card pl-8 text-xs"
                        :placeholder="t('board.filterPlaceholder')"
                    />
                </div>
            </div>

            <div class="overflow-hidden rounded-md border border-border">
                <draggable
                    v-model="reorderableTasks"
                    item-key="id"
                    tag="div"
                    class="divide-y divide-border"
                    handle=".drag-handle"
                    ghost-class="opacity-40"
                    :disabled="!canReorder"
                    @start="handleReorderStart"
                    @end="handleReorderEnd"
                >
                    <template #item="{ element: task }">
                        <div
                            v-show="matchesFilter(task)"
                            class="group flex min-h-14 flex-wrap items-center gap-3 px-4 py-2 transition-colors hover:bg-accent/50"
                            role="button"
                            tabindex="0"
                            @click="openTaskDetail(task)"
                            @keydown.enter="openTaskDetail(task)"
                            @keydown.space.prevent="openTaskDetail(task)"
                        >
                            <div @click.stop @keydown.stop>
                                <GripVertical
                                    v-if="canReorder && !task.completed"
                                    class="drag-handle size-4 shrink-0 cursor-grab text-muted-foreground/40 hover:text-muted-foreground"
                                />
                            </div>
                            <div @click.stop @keydown.stop>
                                <Checkbox
                                    :model-value="task.completed"
                                    :disabled="!canEdit"
                                    :aria-label="`Mark ${task.title} complete`"
                                    @update:model-value="toggleTask(task)"
                                />
                            </div>
                            <div @click.stop @keydown.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            class="size-2.5 shrink-0 rounded-full disabled:cursor-not-allowed"
                                            :class="
                                                priorityDotClass(task.priority)
                                            "
                                            :disabled="!canEdit"
                                            :title="
                                                priorityLabel(task.priority)
                                            "
                                        />
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="start">
                                        <DropdownMenuItem
                                            v-for="option in priorityOptions"
                                            :key="option.value ?? 'none'"
                                            @click="
                                                updateTaskPriority(
                                                    task,
                                                    option.value,
                                                )
                                            "
                                        >
                                            <span
                                                class="size-2.5 rounded-full"
                                                :class="
                                                    priorityDotClass(
                                                        option.value,
                                                    )
                                                "
                                            />
                                            {{ option.label }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <button
                                type="button"
                                class="min-w-0 flex-1 truncate text-left text-sm hover:underline"
                                :class="
                                    task.completed
                                        ? 'text-muted-foreground line-through'
                                        : 'text-foreground'
                                "
                                @click="openTaskDetail(task)"
                            >
                                {{ task.title }}
                            </button>

                            <span
                                v-if="taskEditingLabel(task)"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full bg-brand/10 px-2 py-0.5 text-[11px] font-medium text-brand"
                            >
                                <Pencil class="size-2.5" />
                                {{
                                    t('realtime.editing', {
                                        name: taskEditingLabel(task),
                                    })
                                }}
                            </span>

                            <span
                                v-for="label in task.labels"
                                :key="label.id"
                                class="inline-flex shrink-0 items-center gap-1 rounded-full border border-border px-2 py-0.5 text-[11px] text-muted-foreground"
                            >
                                <span
                                    class="size-1.5 rounded-full"
                                    :class="labelDotClass(label)"
                                />
                                {{ label.name }}
                            </span>

                            <div @click.stop @keydown.stop>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <button
                                            type="button"
                                            :disabled="!canEdit"
                                            class="flex size-7 shrink-0 items-center justify-center rounded text-muted-foreground opacity-100 hover:text-foreground disabled:pointer-events-none sm:opacity-0 sm:group-hover:opacity-100"
                                            :title="t('board.labelsButton')"
                                        >
                                            <Tag class="size-3.5" />
                                        </button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="start"
                                        class="w-56"
                                    >
                                        <DropdownMenuCheckboxItem
                                            v-for="label in board.labels"
                                            :key="label.id"
                                            class="pl-2"
                                            :model-value="hasLabel(task, label)"
                                            @select.prevent
                                            @update:model-value="
                                                (checked) =>
                                                    toggleTaskLabel(
                                                        task,
                                                        label,
                                                        Boolean(checked),
                                                    )
                                            "
                                        >
                                            <template #indicator-icon
                                                ><span
                                            /></template>
                                            <span
                                                class="mr-2 inline-block size-2.5 shrink-0 rounded-full"
                                                :class="[
                                                    labelDotClass(label),
                                                    hasLabel(task, label)
                                                        ? 'ring-2 ring-ring ring-offset-1'
                                                        : '',
                                                ]"
                                            />
                                            {{ label.name }}
                                        </DropdownMenuCheckboxItem>
                                        <p
                                            v-if="board.labels.length === 0"
                                            class="px-2 py-1.5 text-xs text-muted-foreground"
                                        >
                                            {{ t('board.noLabelsYet') }}
                                        </p>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            @select.prevent
                                            @click="labelsOpen = true"
                                        >
                                            {{ t('board.manageLabels') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>

                            <span
                                v-if="task.due_date"
                                class="inline-flex shrink-0 items-center gap-1 text-xs"
                                :class="
                                    isOverdue(task)
                                        ? 'font-medium text-destructive'
                                        : 'text-muted-foreground'
                                "
                            >
                                <CalendarDays class="size-3.5" />
                                {{ formatDueDate(task.due_date) }}
                            </span>

                            <Button
                                v-if="canEdit"
                                variant="ghost"
                                size="icon"
                                class="size-8 text-muted-foreground opacity-100 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100"
                                :title="t('board.deleteTask')"
                                @click.stop="confirmDeleteTask(task)"
                                @keydown.stop
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </template>
                </draggable>
                <div
                    v-if="visibleTasks.length === 0"
                    class="flex flex-col items-center px-6 py-14 text-center"
                >
                    <div
                        class="mb-3 flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand"
                    >
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
            <div class="mb-5">
                <p class="text-sm text-muted-foreground">
                    {{ t('board.notesCount', board.notes.length) }}
                </p>
            </div>

            <NotesPanel
                :board="board"
                :is-authenticated="isAuthenticated"
                :can-edit="canEdit"
                :editing-label-for="editingLabelFor"
                :whisper-editing="whisperEditing"
            />
        </template>
    </div>

    <RenameBoardDialog
        v-if="isAuthenticated"
        v-model:open="renameOpen"
        :board="board"
    />
    <ShareBoardDialog
        v-if="isAuthenticated"
        v-model:open="shareOpen"
        :board="board"
    />
    <LabelManagerDialog
        v-model:open="labelsOpen"
        :board="board"
        :is-authenticated="isAuthenticated"
    />

    <!-- Controlled rather than trigger-based: the trigger would live inside the dropdown,
         which unmounts it on select before the dialog can open. -->
    <AlertDialog v-model:open="deleteOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{
                    t('board.deleteConfirmTitle', { name: board.name })
                }}</AlertDialogTitle>
                <AlertDialogDescription>{{
                    t('board.deleteConfirmBody')
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>{{ t('common.cancel') }}</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/90"
                    @click="deleteBoard"
                >
                    {{ t('board.deleteBoard') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
    <AlertDialog
        v-model:open="deleteTaskOpen"
        @update:open="
            (value) => {
                if (!value) deleteTaskTarget = null;
            }
        "
    >
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{
                    t('board.deleteTaskConfirmTitle', {
                        name: deleteTaskTarget?.title,
                    })
                }}</AlertDialogTitle>
                <AlertDialogDescription>{{
                    t('board.deleteTaskConfirmBody')
                }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>{{ t('common.cancel') }}</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/90"
                    @click="deleteTaskTarget && deleteTask(deleteTaskTarget)"
                >
                    {{ t('board.deleteTask') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
    <TaskDetailDialog
        v-model:open="taskDetailOpen"
        :task="taskDetailTask"
        :board="board"
        :is-authenticated="isAuthenticated"
        :can-edit="canEdit"
    />
</template>
