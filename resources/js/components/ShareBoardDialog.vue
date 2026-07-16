<script setup lang="ts">
import { MailCheck, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { Badge } from '@/components/ui/badge';
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

// Accepted collaborators first, then people who haven't signed up yet.
const members = computed<Collaborator[]>(() => [
    ...(props.board?.collaborators ?? []),
    ...(props.board?.invitations ?? []),
]);

async function invite(): Promise<void> {
    const board = props.board;
    const trimmed = email.value.trim();

    if (!board || !trimmed) {
        return;
    }

    try {
        const member = await request<Collaborator>(`/boards/${board.id}/share`, 'POST', {
            email: trimmed,
            role: role.value,
        });

        // An unknown address comes back as a pending invitation rather than a collaborator.
        if (member.pending) {
            board.invitations = [
                ...board.invitations.filter((item) => item.id !== member.id),
                member,
            ];
            toast.success(t('shareBoard.invitationSent', { email: member.email }));
        } else {
            board.collaborators = [
                ...board.collaborators.filter((item) => item.id !== member.id),
                member,
            ];
        }

        email.value = '';
    } catch {
        toast.error(t('shareBoard.inviteError'));
    }
}

async function updateRole(collaborator: Collaborator, newRole: CollaboratorRole): Promise<void> {
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

async function remove(member: Collaborator): Promise<void> {
    const board = props.board;

    if (!board) {
        return;
    }

    // `id` means different things either side of this branch: an invitation row carries a
    // board_invitations id, an accepted one carries a user id.
    const url = member.pending
        ? `/boards/${board.id}/invitations/${member.id}`
        : `/boards/${board.id}/share/${member.id}`;

    try {
        await request(url, 'DELETE');

        if (member.pending) {
            board.invitations = board.invitations.filter((item) => item.id !== member.id);
        } else {
            board.collaborators = board.collaborators.filter((item) => item.id !== member.id);
        }
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
                <Button type="submit" size="sm" :disabled="!email.trim()">
                    {{ t('shareBoard.invite') }}
                </Button>
            </form>

            <div v-if="members.length" class="mt-4 flex flex-col gap-2">
                <div
                    v-for="member in members"
                    :key="`${member.pending ? 'invite' : 'user'}-${member.id}`"
                    class="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-sm"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5">
                            <span v-if="member.name" class="truncate font-medium text-foreground">
                                {{ member.name }}
                            </span>
                            <span v-else class="truncate font-medium text-muted-foreground">
                                {{ member.email }}
                            </span>
                            <Badge v-if="member.pending" variant="secondary" class="shrink-0 gap-1">
                                <MailCheck class="size-3" />
                                {{ t('shareBoard.pending') }}
                            </Badge>
                        </div>
                        <div v-if="member.name" class="truncate text-xs text-muted-foreground">
                            {{ member.email }}
                        </div>
                        <div v-else class="truncate text-xs text-muted-foreground">
                            {{ t('shareBoard.pendingHint') }}
                        </div>
                    </div>

                    <!-- A pending row has no user to re-target, so the role is fixed until they join. -->
                    <span
                        v-if="member.pending"
                        class="w-24 shrink-0 text-center text-xs text-muted-foreground capitalize"
                    >
                        {{ member.role }}
                    </span>
                    <Select
                        v-else
                        :model-value="member.role"
                        @update:model-value="(value) => updateRole(member, value as CollaboratorRole)"
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
                        :title="member.pending ? t('shareBoard.revokeInvite') : t('shareBoard.removeAccess')"
                        @click="remove(member)"
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
