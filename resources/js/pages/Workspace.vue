<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { LayoutList, LogIn, Plus, UserPlus } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import BoardContent from '@/components/BoardContent.vue';
import AppLogo from '@/components/AppLogo.vue';
import CreateBoardDialog from '@/components/CreateBoardDialog.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { request as apiRequest } from '@/lib/boardApi';
import type { BreadcrumbItem } from '@/types';
import type { Board } from '@/types/board';

const props = defineProps<{ board: Board | null }>();
const page = usePage();
const { t } = useI18n();
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
            description: null,
            completed: false,
            position: 0,
            priority: null,
            due_date: null,
            labels: [],
        },
        {
            id: 'focus',
            title: 'Keep today simple',
            description: null,
            completed: true,
            position: 1,
            priority: null,
            due_date: null,
            labels: [],
        },
    ],
    notes: [],
    labels: [],
    isOwner: true,
    role: 'owner',
    collaborators: [],
    invitations: [],
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

function hasGuestTodos(board: Board): boolean {
    return board.tasks.some((task) => {
        const starterTask = defaultGuestBoard.tasks.find(
            (candidate) => candidate.id === task.id,
        );

        return (
            !starterTask ||
            task.title !== starterTask.title ||
            task.completed !== starterTask.completed
        );
    });
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

        if (!hasGuestTodos(guestBoard)) {
            localStorage.removeItem(storageKey);

            return;
        }

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
        <BoardContent
            v-if="activeBoard"
            :board="activeBoard"
            :is-authenticated="true"
        />

        <div
            v-else
            class="flex min-h-[calc(100vh-4rem)] flex-col items-center justify-center px-6 text-center"
        >
            <div
                class="mb-4 flex size-12 items-center justify-center rounded-md border border-border text-brand"
            >
                <LayoutList class="size-5" />
            </div>
            <h1 class="font-serif text-2xl font-semibold text-foreground">
                {{ t('welcome.startBoardTitle') }}
            </h1>
            <Button
                class="mt-5 bg-brand text-brand-foreground hover:bg-brand/90"
                @click="createOpen = true"
            >
                <Plus class="size-4" /> {{ t('welcome.newBoard') }}
            </Button>
        </div>
    </AppSidebarLayout>

    <div v-else class="flex min-h-svh flex-col bg-background text-foreground">
        <header
            class="flex h-16 items-center justify-between border-b border-border px-4 md:px-8"
        >
            <div class="flex items-center">
                <AppLogo />
            </div>
            <div class="flex items-center gap-2">
                <nav class="hidden items-center gap-1 sm:flex">
                    <Link
                        href="/about"
                        class="rounded-md px-2 py-1 text-sm text-muted-foreground hover:text-foreground"
                        >About</Link
                    >
                    <Link
                        href="/privacy"
                        class="rounded-md px-2 py-1 text-sm text-muted-foreground hover:text-foreground"
                        >Privacy</Link
                    >
                </nav>
                <Button as-child variant="ghost" size="sm">
                    <Link href="/login">
                        <LogIn class="size-4" /> {{ t('welcome.login') }}
                    </Link>
                </Button>
                <Button
                    as-child
                    size="sm"
                    class="bg-brand text-brand-foreground hover:bg-brand/90"
                >
                    <Link href="/register">
                        <UserPlus class="size-4" />
                        {{ t('welcome.createAccount') }}
                    </Link>
                </Button>
            </div>
        </header>

        <div class="flex-1">
            <BoardContent
                v-if="activeBoard"
                :board="activeBoard"
                :is-authenticated="false"
            />
        </div>

        <footer
            class="flex flex-col gap-3 border-t border-border/80 px-4 py-5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between md:px-8"
        >
            <p>© {{ new Date().getFullYear() }} AnchorNotes</p>
            <div class="flex items-center gap-4">
                <LanguageSwitcher />
                <Link href="/about" class="hover:text-foreground">About</Link>
                <Link href="/privacy" class="hover:text-foreground"
                    >Privacy</Link
                >
            </div>
        </footer>
    </div>

    <CreateBoardDialog v-model:open="createOpen" />
    <Toaster v-if="!isAuthenticated" />
</template>
