<script setup lang="ts">
import { computed, ref } from 'vue';

import type { DailyCount } from '@/types/admin';

const props = withDefaults(
    defineProps<{
        points: DailyCount[];
        height?: number;
    }>(),
    { height: 48 },
);

// Plotted in a fixed viewBox and stretched to fit; vector-effect keeps the stroke at a
// true 2px regardless of how far the x-axis is scaled.
const VIEW_WIDTH = 300;

const hoverIndex = ref<number | null>(null);

const max = computed(() => Math.max(1, ...props.points.map((point) => point.count)));

const coordinates = computed(() =>
    props.points.map((point, index) => {
        const x = props.points.length === 1 ? 0 : (index / (props.points.length - 1)) * VIEW_WIDTH;
        const y = props.height - (point.count / max.value) * props.height;

        return { x, y };
    }),
);

const linePath = computed(() =>
    coordinates.value.map((point, index) => `${index === 0 ? 'M' : 'L'}${point.x},${point.y}`).join(' '),
);

const areaPath = computed(() => {
    if (coordinates.value.length === 0) {
        return '';
    }

    const last = coordinates.value[coordinates.value.length - 1];

    return `${linePath.value} L${last.x},${props.height} L0,${props.height} Z`;
});

const hovered = computed(() => (hoverIndex.value === null ? null : props.points[hoverIndex.value]));
const hoveredPoint = computed(() => (hoverIndex.value === null ? null : coordinates.value[hoverIndex.value]));

function onMove(event: MouseEvent): void {
    const target = event.currentTarget as HTMLElement;
    const bounds = target.getBoundingClientRect();
    const ratio = (event.clientX - bounds.left) / bounds.width;

    hoverIndex.value = Math.min(
        props.points.length - 1,
        Math.max(0, Math.round(ratio * (props.points.length - 1))),
    );
}

function formatDay(date: string): string {
    return new Date(date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}
</script>

<template>
    <div class="relative" @mousemove="onMove" @mouseleave="hoverIndex = null">
        <svg
            :viewBox="`0 0 ${VIEW_WIDTH} ${height}`"
            :style="{ height: `${height}px` }"
            class="w-full overflow-visible"
            preserveAspectRatio="none"
            aria-hidden="true"
        >
            <path :d="areaPath" class="fill-brand/10" />
            <path
                :d="linePath"
                fill="none"
                class="stroke-brand"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                vector-effect="non-scaling-stroke"
            />
            <template v-if="hoveredPoint">
                <line
                    :x1="hoveredPoint.x"
                    :x2="hoveredPoint.x"
                    y1="0"
                    :y2="height"
                    class="stroke-border"
                    stroke-width="1"
                    vector-effect="non-scaling-stroke"
                />
                <circle
                    :cx="hoveredPoint.x"
                    :cy="hoveredPoint.y"
                    r="4"
                    class="fill-brand stroke-background"
                    stroke-width="2"
                    vector-effect="non-scaling-stroke"
                />
            </template>
        </svg>

        <!-- Values stay in text tokens; the mark alone carries the series identity. -->
        <div
            v-if="hovered"
            class="pointer-events-none absolute -top-8 rounded-md border border-border bg-popover px-2 py-1 text-xs whitespace-nowrap text-popover-foreground shadow-sm"
            :style="{ left: `${(hoverIndex! / Math.max(1, points.length - 1)) * 100}%`, transform: 'translateX(-50%)' }"
        >
            <span class="font-medium tabular-nums">{{ hovered.count }}</span>
            <span class="text-muted-foreground"> · {{ formatDay(hovered.date) }}</span>
        </div>
    </div>
</template>
