<script setup lang="ts">
import { CalendarDays, Tag, X } from '@lucide/vue';
import { parseDate } from '@internationalized/date';
import type { DateValue } from '@internationalized/date';
import { computed, toRef } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import NoteEditor from '@/components/NoteEditor.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Calendar } from '@/components/ui/calendar';
import {
    Dialog,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { request } from '@/lib/boardApi';
import { labelColorClasses } from '@/lib/labelColors';
import type { Board, Label, Priority, Task } from '@/types/board';

const props = defineProps<{
    task: Task | null;
    board: Board;
    isAuthenticated: boolean;
    canEdit: boolean;
}>();
const task = toRef(props, 'task');
const open = defineModel<boolean>('open', { default: false });
const { t } = useI18n();

const priorityOptions: { value: Priority; label: string }[] = [
    { value: null, label: t('board.priority.none') },
    { value: 'low', label: t('board.priority.low') },
    { value: 'medium', label: t('board.priority.medium') },
    { value: 'high', label: t('board.priority.high') },
];

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
    return task.labels.some((item) => item.id === label.id);
}

function formatDueDate(dueDate: string): string {
    return new Date(dueDate).toLocaleDateString();
}

const dueDateValue = computed<DateValue | undefined>(() => {
    const dueDate = task.value?.due_date;

    return dueDate ? parseDate(dueDate.slice(0, 10)) : undefined;
});

async function saveTitle(task: Task): Promise<void> {
    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/tasks/${task.id}`, 'PATCH', { title: task.title });
    } catch {
        toast.error(t('board.toastUpdateTaskError'));
    }
}

async function toggleCompleted(task: Task): Promise<void> {
    task.completed = !task.completed;

    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/tasks/${task.id}`, 'PATCH', {
            completed: task.completed,
        });
    } catch {
        task.completed = !task.completed;
        toast.error(t('board.toastUpdateTaskError'));
    }
}

async function updatePriority(task: Task, priority: Priority): Promise<void> {
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

async function updateDueDate(task: Task, value: string): Promise<void> {
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

async function selectDueDate(
    task: Task | null,
    value: DateValue | undefined,
    close: () => void,
): Promise<void> {
    if (!task) {
        return;
    }

    await updateDueDate(task, value?.toString() ?? '');

    if (value) {
        close();
    }
}

async function toggleLabel(
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

async function saveDescription(task: Task): Promise<void> {
    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/tasks/${task.id}`, 'PATCH', {
            description: task.description,
        });
    } catch {
        toast.error(t('board.toastUpdateTaskError'));
    }
}

const description = computed<string>({
    get: () => task.value?.description ?? '',
    set: (value) => {
        if (task.value) {
            task.value.description = value;
        }
    },
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogScrollContent class="sm:max-w-2xl">
            <template v-if="task">
                <DialogHeader>
                    <DialogTitle class="sr-only">{{
                        t('taskDetail.dialogTitle')
                    }}</DialogTitle>
                    <div class="flex items-start gap-2">
                        <Checkbox
                            :model-value="task.completed"
                            :disabled="!canEdit"
                            class="mt-1.5"
                            :aria-label="`Mark ${task.title} complete`"
                            @update:model-value="toggleCompleted(task)"
                        />
                        <input
                            :value="task.title"
                            :readonly="!canEdit"
                            class="min-w-0 flex-1 bg-transparent text-lg font-semibold text-foreground outline-none"
                            :class="
                                task.completed
                                    ? 'text-muted-foreground line-through'
                                    : ''
                            "
                            @input="
                                task.title = (
                                    $event.target as HTMLInputElement
                                ).value
                            "
                            @blur="saveTitle(task)"
                        />
                    </div>
                </DialogHeader>

                <div
                    class="flex flex-wrap items-center gap-2 border-b border-border pb-4 text-xs"
                >
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                :disabled="!canEdit"
                                class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1 disabled:cursor-not-allowed"
                            >
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="priorityDotClass(task.priority)"
                                />
                                {{ priorityLabel(task.priority) }}
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="z-[70]">
                            <DropdownMenuItem
                                v-for="option in priorityOptions"
                                :key="option.value ?? 'none'"
                                @click="updatePriority(task, option.value)"
                            >
                                <span
                                    class="size-2.5 rounded-full"
                                    :class="priorityDotClass(option.value)"
                                />
                                {{ option.label }}
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <Popover v-if="canEdit" v-slot="{ close }">
                        <PopoverTrigger as-child>
                            <button
                                type="button"
                                class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                                :title="t('board.dueDate')"
                            >
                                <CalendarDays class="size-3.5" />
                                {{
                                    task.due_date
                                        ? formatDueDate(task.due_date)
                                        : t('board.dueDate')
                                }}
                            </button>
                        </PopoverTrigger>
                        <PopoverContent class="z-[70] w-auto p-0" align="start">
                            <Calendar
                                :model-value="dueDateValue"
                                layout="month-and-year"
                                :initial-focus="true"
                                @update:model-value="
                                    (value) =>
                                        selectDueDate(
                                            task,
                                            value as DateValue | undefined,
                                            close,
                                        )
                                "
                            />
                            <div
                                v-if="task.due_date"
                                class="border-t border-border p-2"
                            >
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="w-full flex items-center gap-2 justify-start text-muted-foreground hover:text-destructive"
                                    @click="
                                        selectDueDate(task, undefined, close)
                                    "
                                >
                                    <X class="size-3.5" />
                                    {{ t('board.clearDueDate') }}
                                </Button>
                            </div>
                        </PopoverContent>
                    </Popover>
                    <span
                        v-else-if="task.due_date"
                        class="rounded-md border border-border px-2 py-1 text-muted-foreground"
                    >
                        {{ formatDueDate(task.due_date) }}
                    </span>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                :disabled="!canEdit"
                                class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1 disabled:cursor-not-allowed"
                            >
                                <Tag class="size-3.5" />
                                {{ t('board.labelsButton') }}
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="z-[70] w-56">
                            <DropdownMenuCheckboxItem
                                v-for="label in board.labels"
                                :key="label.id"
                                class="pl-2"
                                :model-value="hasLabel(task, label)"
                                @select.prevent
                                @update:model-value="
                                    (checked) =>
                                        task &&
                                        toggleLabel(
                                            task,
                                            label,
                                            Boolean(checked),
                                        )
                                "
                            >
                                <template #indicator-icon><span /></template>
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
                        </DropdownMenuContent>
                    </DropdownMenu>

                    <span
                        v-for="label in task.labels"
                        :key="label.id"
                        class="inline-flex items-center gap-1 rounded-full border border-border px-2 py-0.5 text-[11px] text-muted-foreground"
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="labelDotClass(label)"
                        />
                        {{ label.name }}
                    </span>
                </div>

                <NoteEditor
                    v-model="description"
                    :editable="canEdit"
                    :placeholder="t('taskDetail.descriptionPlaceholder')"
                    :image-upload-url="
                        isAuthenticated ? `/tasks/${task.id}/images` : undefined
                    "
                    @save="saveDescription(task)"
                />
            </template>
        </DialogScrollContent>
    </Dialog>
</template>
