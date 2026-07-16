<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Check, LayoutList, LogIn, Plus, UserPlus } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import BoardContent from '@/components/BoardContent.vue';
import CreateBoardDialog from '@/components/CreateBoardDialog.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { request as apiRequest } from '@/lib/boardApi';
import type { BreadcrumbItem } from '@/types';
import type { Board } from '@/types/board';

const props = defineProps<{ board: Board | null }>();
const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const storageKey = 'anchor-guest-board';

const defaultGuestBoard: Board = {
    id: 'local',
    name: 'My tasks',
    icon: 'list-todo',
    tasks: [
        {
            id: 'welcome',
            title: 'Add your first task',
            completed: false,
            position: 0,
            priority: null,
        },
        {
            id: 'focus',
            title: 'Keep today simple',
            completed: true,
            position: 1,
            priority: null,
        },
    ],
    notes: [],
    isOwner: true,
    role: 'owner',
    collaborators: [],
};

const activeBoard = ref<Board | null>(null);
const createOpen = ref(false);

function cloneBoard(source: Board): Board {
    return {
        ...source,
        tasks: [...source.tasks],
        notes: [...source.notes],
        collaborators: [...source.collaborators],
    };
}

watch(
    () => props.board,
    (value) => {
        activeBoard.value = value ? cloneBoard(value) : null;
    },
    { immediate: true },
);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => {
    const items: BreadcrumbItem[] = [{ title: 'Boards', href: '/boards' }];

    if (activeBoard.value) {
        items.push({
            title: activeBoard.value.name,
            href: `/boards/${activeBoard.value.id}`,
        });
    }

    return items;
});

onMounted(async () => {
    if (isAuthenticated.value) {
        await importGuestBoard();

        return;
    }

    try {
        const stored = localStorage.getItem(storageKey);
        activeBoard.value = stored
            ? (JSON.parse(stored) as Board)
            : defaultGuestBoard;
    } catch {
        activeBoard.value = defaultGuestBoard;
    }
});

watch(
    activeBoard,
    (value) => {
        if (!isAuthenticated.value && value) {
            localStorage.setItem(storageKey, JSON.stringify(value));
        }
    },
    { deep: true },
);

async function importGuestBoard(): Promise<void> {
    const stored = localStorage.getItem(storageKey);

    if (!stored) {
        return;
    }

    try {
        const guestBoard = JSON.parse(stored) as Board;
        const created = await apiRequest<Board>('/boards/import', 'POST', {
            name: guestBoard.name,
            icon: guestBoard.icon,
            tasks: guestBoard.tasks.map((task) => ({
                title: task.title,
                completed: task.completed,
            })),
        });

        localStorage.removeItem(storageKey);

        if (!activeBoard.value) {
            router.visit(`/boards/${created.id}`);
        }
    } catch {
        // Keep the local copy so a temporary failure never loses guest tasks.
    }
}
</script>

<template>

    <Head :title="activeBoard?.name" />

    <AppSidebarLayout v-if="isAuthenticated" :breadcrumbs="breadcrumbItems">
        <BoardContent v-if="activeBoard" :board="activeBoard" :is-authenticated="true" />

        <div v-else class="flex min-h-[calc(100vh-4rem)] flex-col items-center justify-center px-6 text-center">
            <div class="mb-4 flex size-12 items-center justify-center rounded-md border border-border text-brand">
                <LayoutList class="size-5" />
            </div>
            <h1 class="font-serif text-2xl font-semibold text-foreground">
                Start with a board
            </h1>
            <Button class="mt-5 bg-brand text-brand-foreground hover:bg-brand/90" @click="createOpen = true">
                <Plus class="size-4" /> New board
            </Button>
        </div>
    </AppSidebarLayout>

    <div v-else class="min-h-screen bg-background text-foreground">
        <header class="flex h-16 items-center justify-between border-b border-border px-4 md:px-8">
            <div class="flex items-center gap-2">
                <div
                    class="flex size-7 items-center justify-center rounded-md bg-brand text-brand-foreground shadow-sm">
                    <Check class="size-4" stroke-width="3" />
                </div>
                <span class="font-serif text-base font-semibold text-foreground">Anchor</span>
            </div>
            <div class="flex items-center gap-2">
                <Button as-child variant="ghost" size="sm">
                    <Link href="/login">
                        <LogIn class="size-4" /> Log in
                    </Link>
                </Button>
                <Button as-child size="sm" class="bg-brand text-brand-foreground hover:bg-brand/90">
                    <Link href="/register">
                        <UserPlus class="size-4" /> Create an account
                    </Link>
                </Button>
            </div>
        </header>

        <BoardContent v-if="activeBoard" :board="activeBoard" :is-authenticated="false" />
    </div>

    <CreateBoardDialog v-model:open="createOpen" />
    <Toaster v-if="!isAuthenticated" />
</template>
