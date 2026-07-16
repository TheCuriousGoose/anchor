<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import BoardIconPicker from '@/components/BoardIconPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { request } from '@/lib/boardApi';
import type { Board } from '@/types/board';

const open = defineModel<boolean>('open', { default: false });
const name = ref('');
const icon = ref('list-todo');
const saving = ref(false);

async function submit(): Promise<void> {
    const trimmed = name.value.trim();

    if (!trimmed) {
        return;
    }

    saving.value = true;

    try {
        const board = await request<Board>('/boards', 'POST', {
            name: trimmed,
            icon: icon.value,
        });
        name.value = '';
        icon.value = 'list-todo';
        open.value = false;
        router.visit(`/boards/${board.id}`);
    } catch {
        toast.error('Could not create the board. Try again.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>New board</DialogTitle>
                <DialogDescription>Give this collection a short, useful
                    name.</DialogDescription>
            </DialogHeader>
            <form @submit.prevent="submit">
                <Input v-model="name" autofocus maxlength="80" placeholder="Product launch" />
                <BoardIconPicker v-model="icon" class="mt-4" />
                <DialogFooter class="mt-5">
                    <Button type="button" variant="outline" @click="open = false">Cancel</Button>
                    <Button type="submit" class="bg-brand text-brand-foreground hover:bg-brand/90"
                        :disabled="!name.trim() || saving">Create board</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
