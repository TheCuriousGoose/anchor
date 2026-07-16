<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { request } from '@/lib/boardApi';
import type { Board, Collaborator, CollaboratorRole } from '@/types/board';

const props = defineProps<{ board: Board | null }>();
const { t } = useI18n();
const open = defineModel<boolean>('open', { default: false });
const email = ref('');
const role = ref<CollaboratorRole>('editor');

async function invite(): Promise<void> {
    const board = props.board;
    const trimmed = email.value.trim();

    if (!board || !trimmed) {
        return;
    }

    try {
        const collaborator = await request<Collaborator>(
            `/boards/${board.id}/share`,
            'POST',
            {
                email: trimmed,
                role: role.value,
            },
        );
        board.collaborators = [
            ...board.collaborators.filter(
                (item) => item.id !== collaborator.id,
            ),
            collaborator,
        ];
        email.value = '';
    } catch {
        toast.error(t('shareBoard.inviteError'));
    }
}

async function updateRole(
    collaborator: Collaborator,
    newRole: CollaboratorRole,
): Promise<void> {
    const board = props.board;

    if (!board) {
        return;
    }

    const previous = collaborator.role;
    collaborator.role = newRole;

    try {
        await request(`/boards/${board.id}/share/${collaborator.id}`, 'PATCH', {
            role: newRole,
        });
    } catch {
        collaborator.role = previous;
        toast.error(t('shareBoard.roleError'));
    }
}

async function removeCollaborator(collaborator: Collaborator): Promise<void> {
    const board = props.board;

    if (!board) {
        return;
    }

    try {
        await request(`/boards/${board.id}/share/${collaborator.id}`, 'DELETE');
        board.collaborators = board.collaborators.filter(
            (item) => item.id !== collaborator.id,
        );
    } catch {
        toast.error(t('shareBoard.removeError'));
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ t('shareBoard.title', { name: board?.name }) }}</DialogTitle>
                <DialogDescription>{{ t('shareBoard.description') }}</DialogDescription>
            </DialogHeader>

            <form class="flex items-center gap-2" @submit.prevent="invite">
                <Input
                    v-model="email"
                    type="email"
                    class="flex-1"
                    :placeholder="t('shareBoard.emailPlaceholder')"
                />
                <Select v-model="role">
                    <SelectTrigger class="w-28">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="editor">{{ t('shareBoard.editor') }}</SelectItem>
                        <SelectItem value="viewer">{{ t('shareBoard.viewer') }}</SelectItem>
                    </SelectContent>
                </Select>
                <Button type="submit" size="sm" :disabled="!email.trim()"
                    >{{ t('shareBoard.invite') }}</Button
                >
            </form>

            <div
                v-if="board?.collaborators.length"
                class="mt-4 flex flex-col gap-2"
            >
                <div
                    v-for="collaborator in board.collaborators"
                    :key="collaborator.id"
                    class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm"
                >
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium text-foreground">
                            {{ collaborator.name }}
                        </div>
                        <div class="truncate text-xs text-muted-foreground">
                            {{ collaborator.email }}
                        </div>
                    </div>
                    <Select
                        :model-value="collaborator.role"
                        @update:model-value="
                            (value) =>
                                updateRole(
                                    collaborator,
                                    value as CollaboratorRole,
                                )
                        "
                    >
                        <SelectTrigger size="sm" class="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="editor">{{ t('shareBoard.editor') }}</SelectItem>
                            <SelectItem value="viewer">{{ t('shareBoard.viewer') }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-8 text-muted-foreground hover:text-destructive"
                        :title="t('shareBoard.removeAccess')"
                        @click="removeCollaborator(collaborator)"
                    >
                        <X class="size-4" />
                    </Button>
                </div>
            </div>
            <p v-else class="mt-4 text-xs text-muted-foreground">
                {{ t('shareBoard.noOneYet') }}
            </p>
        </DialogContent>
    </Dialog>
</template>
