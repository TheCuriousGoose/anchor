<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import type { AppLocale } from '@/lib/i18n';
import { setLocale } from '@/lib/i18n';

const { locale, t } = useI18n();

const options: { value: AppLocale; label: string }[] = [
    { value: 'nl', label: 'NL' },
    { value: 'en', label: 'EN' },
];
</script>

<template>
    <div
        class="flex items-center gap-1 rounded-md border border-border p-0.5"
        :aria-label="t('userMenu.language')"
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
            {{ option.label }}
        </button>
    </div>
</template>
