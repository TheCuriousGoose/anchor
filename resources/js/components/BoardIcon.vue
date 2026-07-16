<script setup lang="ts">
import { Check } from '@lucide/vue';
import { computed } from 'vue';
import { boardIconOptions } from '@/lib/boardIcons';

const props = withDefaults(
    defineProps<{ icon: string; size?: number }>(),
    { size: 16 },
);

const component = computed(
    () =>
        boardIconOptions.find((option) => option.value === props.icon)
            ?.component ?? Check,
);
</script>

<template>
    <component :is="component" :size="size" aria-hidden="true" />
</template>
