<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/AppLogo.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const { t } = useI18n();
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background text-foreground">
        <header
            class="flex h-16 items-center justify-between border-b border-border px-4 md:px-8"
        >
            <div class="flex items-center gap-2">
                <Link href="/" class="flex items-center">
                    <AppLogo />
                </Link>
            </div>
            <nav class="flex items-center gap-2">
                <Link
                    href="/about"
                    class="hidden px-2 py-1 text-sm text-muted-foreground hover:text-foreground sm:inline-flex"
                    >{{ t('public.navigation.about') }}</Link
                >
                <Link
                    href="/privacy"
                    class="hidden px-2 py-1 text-sm text-muted-foreground hover:text-foreground sm:inline-flex"
                    >{{ t('public.navigation.privacy') }}</Link
                >
                <Link
                    :href="isAuthenticated ? '/boards' : '/login'"
                    class="rounded-md bg-brand px-3 py-2 text-sm font-medium text-brand-foreground transition-colors hover:bg-brand/90"
                >
                    {{
                        isAuthenticated
                            ? t('public.navigation.openWorkspace')
                            : t('public.navigation.signIn')
                    }}
                </Link>
            </nav>
        </header>

        <main
            class="mx-auto w-full max-w-3xl flex-1 px-5 py-14 sm:px-8 sm:py-20"
        >
            <slot />
        </main>

        <footer class="w-full border-t border-border/80">
            <div
                class="mx-auto flex w-full max-w-5xl flex-col gap-3 px-5 py-7 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-8"
            >
                <p>
                    {{
                        t('public.footer.copyright', {
                            year: new Date().getFullYear(),
                        })
                    }}
                </p>
                <div class="flex items-center gap-4">
                    <LanguageSwitcher />
                    <Link href="/about" class="hover:text-foreground">{{
                        t('public.navigation.about')
                    }}</Link>
                    <Link href="/privacy" class="hover:text-foreground">{{
                        t('public.navigation.privacy')
                    }}</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
