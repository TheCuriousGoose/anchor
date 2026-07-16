<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="-10 0 431 424"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <g transform="translate(-18 0)">
            <rect
                x="334.514"
                width="85.986"
                height="423.316"
                fill="currentColor"
            />
            <rect
                x="334.592"
                width="61.7335"
                height="441.424"
                transform="rotate(41 334.592 0)"
                fill="currentColor"
            />
            <rect
                x="156.661"
                y="266.587"
                width="61.7335"
                height="263.47"
                transform="rotate(-90 156.661 266.587)"
                fill="currentColor"
            />
            <path
                d="M27.5 225.989C27.5 225.989 27.5 286.489 82.6191 338.983C140.494 394.102 195.614 394.102 195.614 394.102"
                stroke="currentColor"
                stroke-width="55"
            />
        </g>
    </svg>
</template>
