<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

import { Button } from '@/components/ui/button';
import type { Paginated } from '@/types/admin';

const props = defineProps<{
    paginated: Paginated<unknown>;
}>();

const { t } = useI18n();

function go(url: string | null): void {
    if (url === null) {
        return;
    }

    router.visit(url, { preserveState: true, preserveScroll: true, replace: true });
}

function pageUrl(page: number): string | null {
    return props.paginated.links.find((link) => link.label === String(page))?.url ?? null;
}
</script>

<template>
    <div v-if="paginated.last_page > 1" class="flex items-center justify-between gap-4">
        <p class="text-sm text-muted-foreground">
            {{ t('admin.pagination.showing', { from: paginated.from ?? 0, to: paginated.to ?? 0, total: paginated.total }) }}
        </p>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="paginated.current_page === 1"
                @click="go(pageUrl(paginated.current_page - 1))"
            >
                {{ t('admin.pagination.previous') }}
            </Button>
            <span class="text-sm text-muted-foreground tabular-nums">
                {{ t('admin.pagination.page', { current: paginated.current_page, last: paginated.last_page }) }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="paginated.current_page === paginated.last_page"
                @click="go(pageUrl(paginated.current_page + 1))"
            >
                {{ t('admin.pagination.next') }}
            </Button>
        </div>
    </div>
</template>
