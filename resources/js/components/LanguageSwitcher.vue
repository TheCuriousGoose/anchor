<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { AppLocale } from '@/lib/i18n';
import { setLocale } from '@/lib/i18n';

/**
 * `compact` is the pill used in the guest header; `tabs` matches AppearanceTabs so the
 * two controls sit together on the Appearance settings page without clashing.
 */
withDefaults(defineProps<{ variant?: 'compact' | 'tabs' }>(), {
    variant: 'compact',
});

const { locale, t } = useI18n();

const options: { value: AppLocale; short: string; label: string }[] = [
    { value: 'nl', short: 'NL', label: 'Nederlands' },
    { value: 'en', short: 'EN', label: 'English' },
];
</script>

<template>
    <div
        v-if="variant === 'tabs'"
        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
        :aria-label="t('settings.appearance.languageLabel')"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                locale === option.value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
            @click="setLocale(option.value)"
        >
            <span class="text-sm">{{ option.label }}</span>
        </button>
    </div>

    <div
        v-else
        class="flex items-center gap-1 rounded-md border border-border p-0.5"
        :aria-label="t('settings.appearance.languageLabel')"
    >
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="h-6 rounded px-2 text-xs font-medium transition-colors"
            :class="
                locale === option.value
                    ? 'bg-brand/10 text-brand'
                    : 'text-muted-foreground hover:text-foreground'
            "
            @click="setLocale(option.value)"
        >
            {{ option.short }}
        </button>
    </div>
</template>
