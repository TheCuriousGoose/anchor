<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const { t } = useI18n();
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-brand text-brand-foreground shadow-sm"
    >
        <AppLogoIcon class="size-5" />
    </div>
    <div class="ml-2 grid flex-1 text-left leading-tight">
        <span class="truncate font-serif text-sm font-semibold tracking-tight"
            >AnchorNotes</span
        >
        <span
            class="truncate text-[10px] font-medium tracking-[0.16em] text-muted-foreground uppercase"
        >
            {{ t('brand.tagline') }}
        </span>
    </div>
</template>
