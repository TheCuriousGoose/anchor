<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/notifications';

const props = defineProps<{
    preferences: Record<string, boolean>;
    types: string[];
}>();

const { t } = useI18n();

const preferences = ref<Record<string, boolean>>({ ...props.preferences });

function toggle(type: string, value: boolean): void {
    const previous = preferences.value[type];
    preferences.value[type] = value;

    router.patch(
        '/settings/notifications',
        { preferences: preferences.value },
        {
            preserveScroll: true,
            onError: () => {
                preferences.value[type] = previous;
                toast.error(t('settings.notifications.saveError'));
            },
        },
    );
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notification settings', href: edit() }],
    },
});
</script>

<template>
    <Head :title="t('settings.notifications.headTitle')" />

    <h1 class="sr-only">{{ t('settings.notifications.headTitle') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.notifications.title')"
            :description="t('settings.notifications.description')"
        />

        <div class="divide-y divide-border overflow-hidden rounded-lg border border-border">
            <div v-for="type in types" :key="type" class="flex items-start gap-3 p-4">
                <Checkbox
                    :id="`notify-${type}`"
                    :model-value="preferences[type]"
                    class="mt-0.5"
                    @update:model-value="(value) => toggle(type, value === true)"
                />
                <div class="grid gap-1 leading-none">
                    <Label :for="`notify-${type}`" class="cursor-pointer">
                        {{ t(`settings.notifications.types.${type}.label`) }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{ t(`settings.notifications.types.${type}.description`) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
