<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import AdminPagination from '@/components/AdminPagination.vue';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import type { AdminBoard, Paginated } from '@/types/admin';

const props = defineProps<{
    boards: Paginated<AdminBoard>;
    filters: { search: string };
}>();

const { t } = useI18n();
const search = ref(props.filters.search);

let searchTimeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/boards',
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Boards', href: '/admin/boards' },
        ] satisfies BreadcrumbItem[],
    },
});
</script>

<template>
    <Head :title="t('admin.boards.title')" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ t('admin.boards.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('admin.boards.subtitle', { count: boards.total }) }}</p>
        </div>

        <div class="relative max-w-sm">
            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" :placeholder="t('admin.boards.searchPlaceholder')" class="pl-9" />
        </div>

        <div class="overflow-hidden rounded-lg border border-border">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('admin.boards.columnBoard') }}</TableHead>
                            <TableHead>{{ t('admin.boards.columnOwner') }}</TableHead>
                            <TableHead class="text-right">{{ t('admin.boards.columnTasks') }}</TableHead>
                            <TableHead class="text-right">{{ t('admin.boards.columnNotes') }}</TableHead>
                            <TableHead class="text-right">{{ t('admin.boards.columnCollaborators') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="board in boards.data" :key="board.id">
                            <TableCell>
                                <Link :href="`/admin/boards/${board.id}`" class="flex items-center gap-2 hover:underline">
                                    <span>{{ board.icon }}</span>
                                    <span class="font-medium">{{ board.name }}</span>
                                </Link>
                            </TableCell>
                            <TableCell>
                                <p class="text-sm">{{ board.owner.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ board.owner.email }}</p>
                            </TableCell>
                            <TableCell class="text-right tabular-nums">{{ board.tasksCount }}</TableCell>
                            <TableCell class="text-right tabular-nums">{{ board.notesCount }}</TableCell>
                            <TableCell class="text-right tabular-nums">{{ board.collaboratorsCount }}</TableCell>
                        </TableRow>
                        <TableRow v-if="boards.data.length === 0">
                            <TableCell colspan="5" class="py-10 text-center text-sm text-muted-foreground">
                                {{ t('admin.boards.empty') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <AdminPagination :paginated="boards" />
    </div>
</template>
