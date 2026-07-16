<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const { t } = useI18n();
const fileInput = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const removing = ref(false);

function pickFile(): void {
    fileInput.value?.click();
}

function handleFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    uploading.value = true;

    router.post(
        ProfileController.updatePhoto().url,
        { photo: file },
        {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                uploading.value = false;
                input.value = '';
            },
        },
    );
}

function removePhoto(): void {
    removing.value = true;

    router.delete(ProfileController.destroyPhoto().url, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = false;
        },
    });
}
</script>

<template>
    <div class="space-y-4">
        <Heading
            variant="small"
            :title="t('settings.photo.title')"
            :description="t('settings.photo.description')"
        />
        <div class="flex items-center gap-4">
            <Avatar class="size-16">
                <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                <AvatarFallback class="text-lg">{{ getInitials(user.name) }}</AvatarFallback>
            </Avatar>
            <div class="flex items-center gap-2">
                <Button type="button" variant="outline" :disabled="uploading" @click="pickFile">
                    {{ uploading ? t('settings.photo.uploading') : t('settings.photo.upload') }}
                </Button>
                <Button v-if="user.avatar" type="button" variant="ghost" :disabled="removing" @click="removePhoto">
                    {{ t('settings.photo.remove') }}
                </Button>
            </div>
            <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileChange" />
        </div>
    </div>
</template>
