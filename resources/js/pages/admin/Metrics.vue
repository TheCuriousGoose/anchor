<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

import Sparkline from '@/components/Sparkline.vue';
import type { BreadcrumbItem } from '@/types';
import type { DailyCount, MetricTotals, RecentActivity } from '@/types/admin';

const props = defineProps<{
    totals: MetricTotals;
    signupsLast30Days: DailyCount[];
    boardsLast30Days: DailyCount[];
    recentActivity: RecentActivity[];
}>();

const { t } = useI18n();

const tiles = computed(() => [
    { key: 'users', value: props.totals.users },
    { key: 'boards', value: props.totals.boards },
    { key: 'openTasks', value: props.totals.openTasks },
    { key: 'notes', value: props.totals.notes },
]);

const signupsTotal = computed(() => props.signupsLast30Days.reduce((sum, day) => sum + day.count, 0));
const boardsTotal = computed(() => props.boardsLast30Days.reduce((sum, day) => sum + day.count, 0));

function formatDate(value: string | null): string {
    if (value === null) {
        return '';
    }

    return new Date(value).toLocaleString();
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin', href: '/admin' }] satisfies BreadcrumbItem[],
    },
});
</script>

<template>
    <Head :title="t('admin.metrics.title')" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ t('admin.metrics.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('admin.metrics.subtitle') }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="tile in tiles" :key="tile.key" class="rounded-xl border border-border p-4">
                <p class="text-sm text-muted-foreground">{{ t(`admin.metrics.tiles.${tile.key}`) }}</p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">{{ tile.value.toLocaleString() }}</p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="rounded-xl border border-border p-4">
                <div class="flex items-baseline justify-between">
                    <p class="text-sm font-medium">{{ t('admin.metrics.signups') }}</p>
                    <p class="text-sm text-muted-foreground tabular-nums">{{ signupsTotal }}</p>
                </div>
                <Sparkline :points="signupsLast30Days" class="mt-3" />
            </div>
            <div class="rounded-xl border border-border p-4">
                <div class="flex items-baseline justify-between">
                    <p class="text-sm font-medium">{{ t('admin.metrics.boardsCreated') }}</p>
                    <p class="text-sm text-muted-foreground tabular-nums">{{ boardsTotal }}</p>
                </div>
                <Sparkline :points="boardsLast30Days" class="mt-3" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-border p-4">
                <p class="text-sm text-muted-foreground">{{ t('admin.metrics.tiles.admins') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ totals.admins }}</p>
            </div>
            <div class="rounded-xl border border-border p-4">
                <p class="text-sm text-muted-foreground">{{ t('admin.metrics.tiles.suspended') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ totals.suspended }}</p>
            </div>
            <div class="rounded-xl border border-border p-4">
                <p class="text-sm text-muted-foreground">{{ t('admin.metrics.tiles.tasks') }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ totals.tasks }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-border">
            <div class="border-b border-border p-4">
                <p class="text-sm font-medium">{{ t('admin.metrics.recentActivity') }}</p>
            </div>
            <div v-if="recentActivity.length > 0">
                <div
                    v-for="entry in recentActivity"
                    :key="entry.id"
                    class="flex items-center justify-between gap-4 border-b border-border p-4 text-sm last:border-b-0"
                >
                    <div class="min-w-0">
                        <p class="truncate">
                            <span class="font-medium">{{ entry.actor }}</span>
                            <span class="text-muted-foreground"> · {{ entry.action }}</span>
                        </p>
                        <p v-if="entry.target" class="truncate text-xs text-muted-foreground">{{ entry.target }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-muted-foreground">{{ formatDate(entry.createdAt) }}</span>
                </div>
            </div>
            <p v-else class="p-6 text-center text-sm text-muted-foreground">{{ t('admin.metrics.noActivity') }}</p>
        </div>
    </div>
</template>
