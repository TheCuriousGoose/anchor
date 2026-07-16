<script setup lang="ts">
import { Ellipsis } from '@lucide/vue';
import { computed, ref } from 'vue';
import BoardIcon from '@/components/BoardIcon.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { boardIconOptions } from '@/lib/boardIcons';

const icon = defineModel<string>({ required: true });
const primaryIconOptions = computed(() => {
    const selected = boardIconOptions.find((option) => option.value === icon.value);
    const remaining = boardIconOptions.filter(
        (option) => option.value !== icon.value,
    );

    return selected ? [selected, ...remaining.slice(0, 6)] : remaining.slice(0, 7);
});
const moreIconOptions = computed(() =>
    boardIconOptions.filter(
        (option) => !primaryIconOptions.value.includes(option),
    ),
);
const moreIconsOpen = ref(false);
</script>

<template>
    <div class="grid grid-cols-8 gap-2" aria-label="Board icon">
        <button v-for="option in primaryIconOptions" :key="option.value" type="button"
            class="flex h-9 w-full items-center justify-center rounded-md border transition-colors" :class="icon === option.value
                ? 'border-brand bg-brand/10 text-brand'
                : 'border-border text-muted-foreground hover:bg-accent hover:text-foreground'
                " :aria-label="option.label" :title="option.label" @click="icon = option.value">
            <BoardIcon :icon="option.value" />
        </button>
        <DropdownMenu v-model:open="moreIconsOpen">
            <DropdownMenuTrigger as-child>
                <button type="button"
                    class="flex h-9 w-full items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    aria-label="More board icons" title="More board icons">
                    <Ellipsis class="size-4" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-60 p-2">
                <div class="grid grid-cols-6 gap-1" aria-label="More board icons">
                    <button v-for="option in moreIconOptions" :key="option.value" type="button"
                        class="flex size-8 items-center justify-center rounded-sm transition-colors" :class="icon === option.value
                            ? 'bg-brand/10 text-brand'
                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                            " :aria-label="option.label" :title="option.label" @click="
                                icon = option.value;
                            moreIconsOpen = false;
                            ">
                        <BoardIcon :icon="option.value" />
                    </button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
