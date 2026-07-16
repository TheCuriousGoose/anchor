<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    LayoutGrid,
    MoreHorizontal,
    Pencil,
    Plus,
    Share2,
    StickyNote,
    Trash2,
} from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import BoardIcon from '@/components/BoardIcon.vue';
import CreateBoardDialog from '@/components/CreateBoardDialog.vue';
import RenameBoardDialog from '@/components/RenameBoardDialog.vue';
import ShareBoardDialog from '@/components/ShareBoardDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { request } from '@/lib/boardApi';
import type { BreadcrumbItem } from '@/types';
import type { Board } from '@/types/board';

const props = defineProps<{ boards: Board[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Boards', href: '/boards' },
        ] satisfies BreadcrumbItem[],
    },
});

const boards = ref<Board[]>(props.boards);

const createOpen = ref(false);
const renameOpen = ref(false);
const renameTarget = ref<Board | null>(null);
const shareOpen = ref(false);
const shareTarget = ref<Board | null>(null);
const deleteTarget = ref<Board | null>(null);

function canEdit(board: Board): boolean {
    return board.role !== 'viewer';
}

function openCount(board: Board): number {
    return board.tasks.filter((task) => !task.completed).length;
}

function openRename(board: Board): void {
    renameTarget.value = board;
    renameOpen.value = true;
}

function openShare(board: Board): void {
    shareTarget.value = board;
    shareOpen.value = true;
}

async function confirmDelete(): Promise<void> {
    const board = deleteTarget.value;

    if (!board) {
        return;
    }

    try {
        await request(`/boards/${board.id}`, 'DELETE');
        boards.value = boards.value.filter((item) => item.id !== board.id);
        deleteTarget.value = null;
        router.reload({ only: ['sidebarBoards'] });
    } catch {
        toast.error('Could not delete the board. Try again.');
    }
}
</script>

<template>

    <Head title="Boards" />

    <div class="space-y-6 p-4 md:p-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-foreground">
                    Boards
                </h1>
                <p class="text-sm text-muted-foreground">
                    Boards you own or have been given access to.
                </p>
            </div>
            <Button class="bg-brand text-brand-foreground hover:bg-brand/90" @click="createOpen = true">
                <Plus class="size-4" /> New board
            </Button>
        </div>

        <div v-if="boards.length === 0"
            class="flex flex-col items-center rounded-xl border border-dashed border-border py-16 text-center">
            <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
                <LayoutGrid class="size-5" />
            </div>
            <p class="text-sm font-medium text-foreground">No boards yet</p>
            <p class="mt-1 text-xs text-muted-foreground">
                Create your first board to start tracking tasks and notes.
            </p>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="board in boards" :key="board.id"
                class="group relative rounded-xl border border-border p-5 transition-colors hover:bg-accent/50">
                <Link :href="`/boards/${board.id}`" class="absolute inset-0 z-0" :aria-label="`Open ${board.name}`" />

                <div class="pointer-events-none relative z-10 mb-3 flex items-start justify-between">
                    <div
                        class="flex size-10 items-center justify-center rounded-md border border-border bg-background text-lg">
                        <BoardIcon :icon="board.icon" :size="20" />
                    </div>
                    <div class="pointer-events-auto">
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="ghost" size="icon"
                                    class="size-8 text-muted-foreground opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                                    title="Board options">
                                    <MoreHorizontal class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem v-if="canEdit(board)" @click="openRename(board)">
                                    <Pencil class="size-4" /> Rename
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="board.isOwner" @click="openShare(board)">
                                    <Share2 class="size-4" /> Share
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="board.isOwner" variant="destructive"
                                    @click="deleteTarget = board">
                                    <Trash2 class="size-4" /> Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div class="pointer-events-none relative z-10">
                    <h3 class="truncate font-serif text-lg font-semibold text-foreground">
                        {{ board.name }}
                    </h3>
                    <div class="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
                        <span>{{ openCount(board) }} open</span>
                        <span>·</span>
                        <span class="inline-flex items-center gap-1">
                            <StickyNote class="size-3" />{{
                                board.notes.length
                            }}
                        </span>
                        <Badge v-if="!board.isOwner" variant="secondary" class="ml-auto capitalize">{{ board.role }}
                        </Badge>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <CreateBoardDialog v-model:open="createOpen" />
    <RenameBoardDialog v-model:open="renameOpen" :board="renameTarget" />
    <ShareBoardDialog v-model:open="shareOpen" :board="shareTarget" />

    <Dialog :open="deleteTarget !== null" @update:open="
        (open) => {
            if (!open) deleteTarget = null;
        }
    ">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete “{{ deleteTarget?.name }}”?</DialogTitle>
                <DialogDescription>This permanently deletes the board and all of its tasks and
                    notes. This cannot be undone.</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" variant="outline" @click="deleteTarget = null">Cancel</Button>
                <Button type="button" variant="destructive" @click="confirmDelete">Delete board</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
