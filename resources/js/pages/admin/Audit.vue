<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import AdminPagination from '@/components/AdminPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';
import type { AuditLogEntry, Paginated } from '@/types/admin';

const props = defineProps<{
    logs: Paginated<AuditLogEntry>;
    filters: { action: string; search: string };
    actions: string[];
}>();

const { t } = useI18n();

const ALL_ACTIONS = 'all';

const search = ref(props.filters.search);
const action = ref(props.filters.action === '' ? ALL_ACTIONS : props.filters.action);

let searchTimeout: ReturnType<typeof setTimeout>;

function reload(): void {
    router.get(
        '/admin/audit',
        {
            search: search.value,
            action: action.value === ALL_ACTIONS ? '' : action.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 300);
});

watch(action, reload);

function formatDate(value: string | null): string {
    if (value === null) {
        return '';
    }

    return new Date(value).toLocaleString();
}

/** Destructive actions read as destructive at a glance. */
function isDestructive(value: string): boolean {
    return value.includes('deleted') || value.includes('suspended') || value.includes('revoked');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Audit log', href: '/admin/audit' },
        ] satisfies BreadcrumbItem[],
    },
});
</script>

<template>
    <Head :title="t('admin.audit.title')" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ t('admin.audit.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('admin.audit.subtitle', { count: logs.total }) }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="relative max-w-sm flex-1">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" :placeholder="t('admin.audit.searchPlaceholder')" class="pl-9" />
            </div>
            <Select v-model="action">
                <SelectTrigger class="w-56">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL_ACTIONS">{{ t('admin.audit.allActions') }}</SelectItem>
                    <SelectItem v-for="value in actions" :key="value" :value="value">{{ value }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="overflow-hidden rounded-lg border border-border">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('admin.audit.columnWhen') }}</TableHead>
                            <TableHead>{{ t('admin.audit.columnActor') }}</TableHead>
                            <TableHead>{{ t('admin.audit.columnAction') }}</TableHead>
                            <TableHead>{{ t('admin.audit.columnTarget') }}</TableHead>
                            <TableHead>{{ t('admin.audit.columnIp') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in logs.data" :key="log.id">
                            <TableCell class="whitespace-nowrap text-xs text-muted-foreground">
                                {{ formatDate(log.createdAt) }}
                            </TableCell>
                            <TableCell class="text-sm">{{ log.actor }}</TableCell>
                            <TableCell>
                                <Badge :variant="isDestructive(log.action) ? 'destructive' : 'secondary'">
                                    {{ log.action }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-sm">{{ log.targetLabel ?? '—' }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ log.ipAddress ?? '—' }}</TableCell>
                        </TableRow>
                        <TableRow v-if="logs.data.length === 0">
                            <TableCell colspan="5" class="py-10 text-center text-sm text-muted-foreground">
                                {{ t('admin.audit.empty') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <AdminPagination :paginated="logs" />
    </div>
</template>
