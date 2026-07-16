<script setup lang="ts">
import { Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('labelManager.title') }}</DialogTitle>
                <DialogDescription>{{
                    t('labelManager.description')
                }}</DialogDescription>
            </DialogHeader>

            <form class="flex items-center gap-2" @submit.prevent="createLabel">
                <Input
                    v-model="newLabelName"
                    class="flex-1"
                    maxlength="50"
                    :placeholder="t('labelManager.namePlaceholder')"
                />
                <div class="flex items-center gap-1">
                    <button
                        v-for="color in labelColors"
                        :key="color"
                        type="button"
                        class="size-5 shrink-0 rounded-full ring-offset-2 ring-offset-background transition-all"
                        :class="[
                            labelColorClasses[color],
                            newLabelColor === color ? 'ring-2 ring-ring' : '',
                        ]"
                        @click="newLabelColor = color"
                    />
                </div>
                <Button type="submit" size="sm" :disabled="!newLabelName.trim()">
                    {{ t('labelManager.add') }}
                </Button>
            </form>

            <div v-if="board?.labels.length" class="mt-4 flex flex-col gap-2">
                <div
                    v-for="label in board.labels"
                    :key="label.id"
                    class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm"
                >
                    <span
                        class="size-2.5 shrink-0 rounded-full"
                        :class="labelColorClasses[label.color]"
                    />
                    <span class="min-w-0 flex-1 truncate">{{ label.name }}</span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 text-muted-foreground hover:text-destructive"
                        :title="t('labelManager.delete')"
                        @click="deleteLabel(label)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
            <p v-else class="mt-4 text-xs text-muted-foreground">
                {{ t('labelManager.empty') }}
            </p>
        </DialogContent>
    </Dialog>
</template>
