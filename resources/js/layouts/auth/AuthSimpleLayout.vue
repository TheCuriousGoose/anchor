<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex items-center font-medium">
                        <AppLogo />
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
                <p class="text-center text-xs leading-5 text-muted-foreground">
                    <Link
                        href="/privacy"
                        class="underline underline-offset-4 hover:text-foreground"
                        >Privacy</Link
                    >
                    <span class="px-2">·</span>
                    <Link
                        href="/about"
                        class="underline underline-offset-4 hover:text-foreground"
                        >About AnchorNotes</Link
                    >
                </p>
            </div>
        </div>
    </div>
</template>
