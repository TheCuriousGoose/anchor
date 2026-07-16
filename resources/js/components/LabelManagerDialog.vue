<script setup lang="ts">
import { Check, Plus, Tag, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { request } from '@/lib/boardApi';
import { labelColorClasses, labelColors } from '@/lib/labelColors';
import type { Board, Label, LabelColor } from '@/types/board';

const props = defineProps<{ board: Board | null; isAuthenticated: boolean }>();
const open = defineModel<boolean>('open', { default: false });
const { t } = useI18n();

const newLabelName = ref('');
const newLabelColor = ref<LabelColor>('blue');
const labelUsageCounts = computed(() => {
    const counts = new Map<string, number>();

    props.board?.tasks.forEach((task) => {
        task.labels.forEach((label) => {
            counts.set(label.id, (counts.get(label.id) ?? 0) + 1);
        });
    });

    return counts;
});

async function createLabel(): Promise<void> {
    const board = props.board;
    const name = newLabelName.value.trim();

    if (!board || !name) {
        return;
    }

    try {
        if (props.isAuthenticated) {
            const label = await request<Label>(
                `/boards/${board.id}/labels`,
                'POST',
                { name, color: newLabelColor.value },
            );
            board.labels.push(label);
        } else {
            board.labels.push({
                id: crypto.randomUUID(),
                name,
                color: newLabelColor.value,
            });
        }

        newLabelName.value = '';
    } catch {
        toast.error(t('board.toastCreateLabelError'));
    }
}

async function deleteLabel(label: Label): Promise<void> {
    const board = props.board;

    if (!board) {
        return;
    }

    try {
        if (props.isAuthenticated) {
            await request(`/labels/${label.id}`, 'DELETE');
        }

        board.labels = board.labels.filter((item) => item.id !== label.id);

        board.tasks.forEach((task) => {
            task.labels = task.labels.filter((item) => item.id !== label.id);
        });
    } catch {
        toast.error(t('board.toastDeleteLabelError'));
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-lg">
            <DialogHeader>
                <div class="flex items-start gap-3 px-6 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand"
                    >
                        <Tag class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle>{{ t('labelManager.title') }}</DialogTitle>
                        <DialogDescription class="mt-1">{{
                            t('labelManager.description')
                        }}</DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="px-6 pt-6 pb-5">
                <form
                    class="rounded-lg border border-border bg-muted/30 p-3"
                    @submit.prevent="createLabel"
                >
                    <div class="flex gap-2">
                        <Input
                            v-model="newLabelName"
                            class="bg-background"
                            maxlength="50"
                            :placeholder="t('labelManager.namePlaceholder')"
                        />
                        <Button
                            type="submit"
                            class="shrink-0"
                            :disabled="!newLabelName.trim()"
                        >
                            <Plus class="size-4" />
                            {{ t('labelManager.add') }}
                        </Button>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button
                            v-for="color in labelColors"
                            :key="color"
                            type="button"
                            class="flex size-6 shrink-0 items-center justify-center rounded-full ring-offset-2 ring-offset-muted transition-transform hover:scale-110 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            :class="[
                                labelColorClasses[color],
                                newLabelColor === color
                                    ? 'scale-110 ring-2 ring-ring'
                                    : '',
                            ]"
                            :aria-label="color"
                            :aria-pressed="newLabelColor === color"
                            @click="newLabelColor = color"
                        >
                            <Check
                                v-if="newLabelColor === color"
                                class="size-3.5 text-white"
                            />
                        </button>
                    </div>
                </form>

                <div
                    v-if="board?.labels.length"
                    class="mt-6 overflow-hidden rounded-lg border border-border"
                >
                    <div
                        class="flex items-center justify-between border-b border-border bg-muted/30 px-3 py-2 text-xs font-medium text-muted-foreground"
                    >
                        <span>{{ t('labelManager.title') }}</span>
                        <span>{{ board.labels.length }}</span>
                    </div>
                    <div class="divide-y divide-border">
                        <div
                            v-for="label in board.labels"
                            :key="label.id"
                            class="group flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-muted/40"
                        >
                            <span
                                class="size-3 shrink-0 rounded-full ring-2 ring-background"
                                :class="labelColorClasses[label.color]"
                            />
                            <span
                                class="min-w-0 flex-1 truncate text-sm font-medium"
                                >{{ label.name }}</span
                            >
                            <span
                                class="text-xs text-muted-foreground"
                                :title="`${labelUsageCounts.get(label.id) ?? 0} tasks`"
                            >
                                {{ labelUsageCounts.get(label.id) ?? 0 }}
                            </span>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                                :title="t('labelManager.delete')"
                                @click="deleteLabel(label)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
                <p
                    v-else
                    class="mt-6 rounded-lg border border-dashed border-border px-4 py-7 text-center text-sm text-muted-foreground"
                >
                    {{ t('labelManager.empty') }}
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
