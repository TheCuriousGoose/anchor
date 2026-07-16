<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { useEventListener } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import BoardIcon from '@/components/BoardIcon.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';

const open = defineModel<boolean>('open', { default: false });
const query = ref('');
const page = usePage();

const results = computed(() => {
    const needle = query.value.trim().toLowerCase();
    const boards = page.props.sidebarBoards;

    return needle
        ? boards.filter((board) => board.name.toLowerCase().includes(needle))
        : boards;
});

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === 'k' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        open.value = !open.value;
    }
});

watch(open, (value) => {
    if (!value) {
        query.value = '';
    }
});

function goToBoard(boardId: string): void {
    open.value = false;
    router.visit(`/boards/${boardId}`);
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-md">
            <DialogTitle class="sr-only">Search boards</DialogTitle>
            <DialogDescription class="sr-only">Search and jump to one of your boards.</DialogDescription>
            <div class="flex items-center gap-2 border-b border-border px-3">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <Input v-model="query" autofocus placeholder="Search boards…"
                    class="h-11 border-0 bg-transparent shadow-none focus-visible:ring-0" />
            </div>
            <div class="max-h-72 overflow-y-auto p-2">
                <button v-for="board in results" :key="board.id" type="button"
                    class="flex w-full items-center gap-2 rounded-md px-2 py-2 text-left text-sm hover:bg-accent"
                    @click="goToBoard(board.id)">
                    <BoardIcon :icon="board.icon" :size="20" class="shrink-0" />
                    <span class="min-w-0 flex-1 truncate">{{
                        board.name
                        }}</span>
                </button>
                <p v-if="results.length === 0" class="px-2 py-6 text-center text-sm text-muted-foreground">
                    No boards found.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
