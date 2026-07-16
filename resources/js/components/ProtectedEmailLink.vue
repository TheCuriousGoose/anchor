<script setup lang="ts">
import { onMounted, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        encoded?: string;
    }>(),
    {
        encoded: 'Y29udGFjdEBhbmNob3Itbm90ZXMubmw=',
    },
);

const email = ref('');

onMounted(() => {
    email.value = atob(props.encoded);
});
</script>

<template>
    <a
        v-if="email"
        :href="`mailto:${email}`"
        class="font-medium text-brand underline underline-offset-4 hover:text-brand/80"
    >
        {{ email }}
    </a>
    <span v-else aria-hidden="true">•••••••••••••••••••••••</span>
</template>
