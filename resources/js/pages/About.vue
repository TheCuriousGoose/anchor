<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { NotebookPen, ShieldCheck, UsersRound } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import PublicPageShell from '@/components/PublicPageShell.vue';
import ProtectedEmailLink from '@/components/ProtectedEmailLink.vue';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('public.about.pageTitle')" />

    <PublicPageShell>
        <section class="max-w-2xl">
            <p class="text-sm font-medium text-brand">
                {{ t('public.about.eyebrow') }}
            </p>
            <h1
                class="mt-2 font-serif text-3xl font-semibold tracking-tight sm:text-4xl"
            >
                AnchorNotes
            </h1>
            <p class="mt-5 text-base leading-7 text-muted-foreground">
                {{ t('public.about.introduction') }}
            </p>
        </section>

        <section class="mt-12 divide-y divide-border border-y border-border">
            <article class="grid gap-3 py-6 sm:grid-cols-[2rem_1fr] sm:gap-5">
                <NotebookPen class="size-5 text-brand" />
                <div>
                    <h2 class="font-semibold">
                        {{ t('public.about.context.title') }}
                    </h2>
                    <p class="mt-2 leading-7 text-muted-foreground">
                        {{ t('public.about.context.description') }}
                    </p>
                </div>
            </article>
            <article class="grid gap-3 py-6 sm:grid-cols-[2rem_1fr] sm:gap-5">
                <UsersRound class="size-5 text-brand" />
                <div>
                    <h2 class="font-semibold">
                        {{ t('public.about.sharing.title') }}
                    </h2>
                    <p class="mt-2 leading-7 text-muted-foreground">
                        {{ t('public.about.sharing.description') }}
                    </p>
                </div>
            </article>
            <article class="grid gap-3 py-6 sm:grid-cols-[2rem_1fr] sm:gap-5">
                <ShieldCheck class="size-5 text-brand" />
                <div>
                    <h2 class="font-semibold">
                        {{ t('public.about.simple.title') }}
                    </h2>
                    <p class="mt-2 leading-7 text-muted-foreground">
                        {{ t('public.about.simple.description') }}
                    </p>
                </div>
            </article>
        </section>

        <section
            class="mt-10 pt-6 text-sm leading-7 text-muted-foreground bg-black/5 dark:bg-white/5 rounded-lg border border-border p-6"
        >
            <p>
                {{ t('public.about.openSourcePrefix') }}
                <a
                    href="https://github.com/TheCuriousGoose/anchor"
                    target="_blank"
                    rel="noreferrer"
                    class="font-medium text-brand underline underline-offset-4 hover:text-brand/80"
                    >{{ t('public.github') }}</a
                >.
            </p>
            <p class="mt-2">
                {{ t('public.about.contactPrefix') }}
                <ProtectedEmailLink />.
            </p>
            <Link
                href="/"
                class="mt-4 inline-block font-medium text-brand underline underline-offset-4 hover:text-brand/80"
                >{{ t('public.about.openApp') }}</Link
            >
        </section>
    </PublicPageShell>
</template>
