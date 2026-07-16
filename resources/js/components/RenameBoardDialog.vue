<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import BoardIconPicker from '@/components/BoardIconPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { request } from '@/lib/boardApi';
import type { Board } from '@/types/board';

const props = defineProps<{ board: Board | null }>();
const { t } = useI18n();
const open = defineModel<boolean>('open', { default: false });
const name = ref('');
const icon = ref('list-todo');
const saving = ref(false);

watch(open, (value) => {
    if (value) {
        name.value = props.board?.name ?? '';
        icon.value = props.board?.icon ?? 'list-todo';
    }
});

async function submit(): Promise<void> {
    const board = props.board;
    const trimmed = name.value.trim();

    if (!board || !trimmed) {
        return;
    }

    saving.value = true;

    try {
        const updated = await request<Board>(`/boards/${board.id}`, 'PATCH', {
            name: trimmed,
            icon: icon.value,
        });
        board.name = updated.name;
        board.icon = updated.icon;
        open.value = false;
        router.reload({ only: ['sidebarBoards'] });
    } catch {
        toast.error(t('renameBoard.error'));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('renameBoard.title') }}</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submit">
                <Input v-model="name" autofocus maxlength="80" />
                <BoardIconPicker v-model="icon" class="mt-4" />
                <DialogFooter class="mt-5">
                    <Button type="button" variant="outline" @click="open = false">{{ t('common.cancel') }}</Button>
                    <Button type="submit" :disabled="!name.trim() || saving">{{ t('common.save') }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
