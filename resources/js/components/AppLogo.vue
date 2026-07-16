<script setup lang="ts">
import { Check } from '@lucide/vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-brand text-brand-foreground shadow-sm"
    >
        <Check class="size-4" stroke-width="3" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-serif leading-tight font-semibold"
            >Anchor</span
        >
    </div>
</template>
