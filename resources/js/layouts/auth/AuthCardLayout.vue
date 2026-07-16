<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted p-6 md:p-10"
    >
        <div class="flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center self-center font-medium"
            >
                <AppLogo />
            </Link>

            <div class="flex flex-col gap-6">
                <Card class="rounded-xl">
                    <CardHeader class="px-10 pt-8 pb-0 text-center">
                        <CardTitle class="text-xl">{{ title }}</CardTitle>
                        <CardDescription>
                            {{ description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="px-10 py-8">
                        <slot />
                    </CardContent>
                </Card>
            </div>
            <p class="text-center text-xs text-muted-foreground">
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
</template>
