<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import { useI18n } from 'vue-i18n';

import { Badge } from '@/components/ui/badge';
import type { BreadcrumbItem } from '@/types';
import type { AdminBoardDetail } from '@/types/admin';

const props = defineProps<{
    board: AdminBoardDetail;
}>();

const { t } = useI18n();

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
    <Head :title="props.board.name" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
                <span class="text-2xl">{{ board.icon }}</span>
                <h1 class="text-2xl font-semibold tracking-tight">{{ board.name }}</h1>
            </div>
            <p class="text-sm text-muted-foreground">
                {{ t('admin.boardDetail.ownedBy', { name: board.owner.name, email: board.owner.email }) }}
            </p>
        </div>

        <!-- Oversight is read-only by design; nothing here mutates the board. -->
        <div class="flex items-center gap-2 rounded-lg border border-border bg-muted/40 p-3 text-sm text-muted-foreground">
            <Eye class="size-4 shrink-0" />
            <span>{{ t('admin.boardDetail.readOnlyNotice') }}</span>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-border">
                <div class="border-b border-border p-4">
                    <p class="text-sm font-medium">{{ t('admin.boardDetail.tasks', { count: board.tasks.length }) }}</p>
                </div>
                <div v-if="board.tasks.length > 0">
                    <div
                        v-for="task in board.tasks"
                        :key="task.id"
                        class="flex items-center justify-between gap-3 border-b border-border p-3 text-sm last:border-b-0"
                    >
                        <span :class="task.completed ? 'text-muted-foreground line-through' : ''">{{ task.title }}</span>
                        <Badge v-if="task.priority" variant="secondary" class="shrink-0 capitalize">{{ task.priority }}</Badge>
                    </div>
                </div>
                <p v-else class="p-6 text-center text-sm text-muted-foreground">{{ t('admin.boardDetail.noTasks') }}</p>
            </div>

            <div class="flex flex-col gap-4">
                <div class="rounded-xl border border-border">
                    <div class="border-b border-border p-4">
                        <p class="text-sm font-medium">
                            {{ t('admin.boardDetail.collaborators', { count: board.collaborators.length }) }}
                        </p>
                    </div>
                    <div v-if="board.collaborators.length > 0">
                        <div
                            v-for="collaborator in board.collaborators"
                            :key="collaborator.id"
                            class="flex items-center justify-between gap-3 border-b border-border p-3 last:border-b-0"
                        >
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ collaborator.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ collaborator.email }}</p>
                            </div>
                            <Badge variant="secondary" class="shrink-0 capitalize">{{ collaborator.role }}</Badge>
                        </div>
                    </div>
                    <p v-else class="p-6 text-center text-sm text-muted-foreground">
                        {{ t('admin.boardDetail.noCollaborators') }}
                    </p>
                </div>

                <div class="rounded-xl border border-border">
                    <div class="border-b border-border p-4">
                        <p class="text-sm font-medium">{{ t('admin.boardDetail.notes', { count: board.notes.length }) }}</p>
                    </div>
                    <div v-if="board.notes.length > 0">
                        <div
                            v-for="note in board.notes"
                            :key="note.id"
                            class="border-b border-border p-3 text-sm last:border-b-0"
                        >
                            {{ note.title }}
                        </div>
                    </div>
                    <p v-else class="p-6 text-center text-sm text-muted-foreground">{{ t('admin.boardDetail.noNotes') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
