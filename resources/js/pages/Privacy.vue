<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import PublicPageShell from '@/components/PublicPageShell.vue';
import ProtectedEmailLink from '@/components/ProtectedEmailLink.vue';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('public.privacy.pageTitle')" />

    <PublicPageShell>
        <section class="max-w-2xl">
            <p class="text-sm font-medium text-brand">
                {{ t('public.privacy.eyebrow') }}
            </p>
            <h1
                class="mt-2 font-serif text-3xl font-semibold tracking-tight sm:text-4xl"
            >
                {{ t('public.privacy.heading') }}
            </h1>
            <p class="mt-4 text-sm text-muted-foreground">
                {{ t('public.privacy.lastUpdated') }}
            </p>
        </section>

        <div
            class="mt-12 space-y-10 text-sm leading-7 text-muted-foreground sm:text-base"
        >
            <section>
                <h2 class="font-serif text-2xl font-semibold text-foreground">
                    {{ t('public.privacy.scope.title') }}
                </h2>
                <p class="mt-3">
                    {{ t('public.privacy.scope.description') }}
                </p>
            </section>

            <section>
                <h2 class="font-serif text-2xl font-semibold text-foreground">
                    {{ t('public.privacy.workspaceData.title') }}
                </h2>
                <p class="mt-3">
                    {{ t('public.privacy.workspaceData.description') }}
                </p>
            </section>

            <section>
                <h2 class="font-serif text-2xl font-semibold text-foreground">
                    {{ t('public.privacy.access.title') }}
                </h2>
                <p class="mt-3">
                    {{ t('public.privacy.access.description') }}
                </p>
            </section>

            <section>
                <h2 class="font-serif text-2xl font-semibold text-foreground">
                    {{ t('public.privacy.retention.title') }}
                </h2>
                <p class="mt-3">
                    {{ t('public.privacy.retention.description') }}
                </p>
            </section>

            <section>
                <h2 class="font-serif text-2xl font-semibold text-foreground">
                    {{ t('public.privacy.changes.title') }}
                </h2>
                <p class="mt-3">
                    {{ t('public.privacy.changes.description') }}
                </p>
            </section>
        </div>

        <aside
            class="mt-14 rounded-xl border border-border bg-muted/50 p-5 text-sm leading-6 text-muted-foreground"
        >
            <strong class="text-foreground">{{
                t('public.privacy.help.title')
            }}</strong>
            <p class="mt-1">
                {{ t('public.privacy.help.contactPrefix') }}
                <ProtectedEmailLink />.
            </p>
            <p class="mt-3">
                {{ t('public.privacy.help.sourcePrefix') }}
                <a
                    href="https://github.com/TheCuriousGoose/anchor"
                    target="_blank"
                    rel="noreferrer"
                    class="font-medium text-brand underline underline-offset-4 hover:text-brand/80"
                    >{{ t('public.github') }}</a
                >.
            </p>
        </aside>
    </PublicPageShell>
</template>
