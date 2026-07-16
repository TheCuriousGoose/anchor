<script setup lang="ts">
import { useI18n } from 'vue-i18n';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import type { AdminUser } from '@/types/admin';

defineProps<{
    user: AdminUser;
}>();

defineEmits<{
    confirm: [];
}>();

const { t } = useI18n();
</script>

<template>
    <AlertDialog>
        <AlertDialogTrigger as-child>
            <slot />
        </AlertDialogTrigger>
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ t('admin.users.deleteTitle', { name: user.name }) }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ t('admin.users.deleteDescription') }}
                    <!-- boards.user_id cascades, so this is not just an account deletion. -->
                    <strong v-if="user.boardsCount > 0" class="mt-2 block text-destructive">
                        {{ t('admin.users.deleteBoardsWarning', { count: user.boardsCount }) }}
                    </strong>
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>{{ t('common.cancel') }}</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-white hover:bg-destructive/90"
                    @click="$emit('confirm')"
                >
                    {{ t('admin.users.deleteConfirm') }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
