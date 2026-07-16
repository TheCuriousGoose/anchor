<script setup lang="ts">
import { Eye, MailCheck, Pencil, UserPlus, UsersRound, X } from '@lucide/vue';
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
        const member = await request<Collaborator>(
            `/boards/${board.id}/share`,
            'POST',
            {
                email: trimmed,
                role: role.value,
            },
        );

        // An unknown address comes back as a pending invitation rather than a collaborator.
        if (member.pending) {
            board.invitations = [
                ...board.invitations.filter((item) => item.id !== member.id),
                member,
            ];
            toast.success(
                t('shareBoard.invitationSent', { email: member.email }),
            );
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

async function updateRole(
    collaborator: Collaborator,
    newRole: CollaboratorRole,
): Promise<void> {
    const board = props.board;

    if (!board || collaborator.role === newRole) {
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
            board.invitations = board.invitations.filter(
                (item) => item.id !== member.id,
            );
        } else {
            board.collaborators = board.collaborators.filter(
                (item) => item.id !== member.id,
            );
        }
    } catch {
        toast.error(t('shareBoard.removeError'));
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="gap-0 overflow-hidden p-0 sm:max-w-lg">
            <DialogHeader>
                <div class="flex items-start gap-3 px-6 pt-6">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand"
                    >
                        <UsersRound class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <DialogTitle>{{
                            t('shareBoard.title', { name: board?.name })
                        }}</DialogTitle>
                        <DialogDescription class="mt-1">{{
                            t('shareBoard.description')
                        }}</DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div class="px-6 pt-6 pb-5">
                <form
                    class="rounded-lg border border-border bg-muted/30 p-3"
                    @submit.prevent="invite"
                >
                    <div class="flex gap-2">
                        <Input
                            v-model="email"
                            type="email"
                            class="bg-background"
                            :placeholder="t('shareBoard.emailPlaceholder')"
                        />
                        <Button
                            type="submit"
                            class="shrink-0"
                            :disabled="!email.trim()"
                        >
                            <UserPlus class="size-4" />
                            {{ t('shareBoard.invite') }}
                        </Button>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-xs font-medium text-muted-foreground">
                            {{ t('shareBoard.editor') }} /
                            {{ t('shareBoard.viewer') }}
                        </span>
                        <div
                            class="inline-flex rounded-md border border-border bg-background p-0.5"
                            role="group"
                            :aria-label="t('shareBoard.editor')"
                        >
                            <button
                                type="button"
                                class="inline-flex h-7 items-center gap-1.5 rounded-sm px-2 text-xs font-medium transition-colors"
                                :class="
                                    role === 'editor'
                                        ? 'bg-brand text-brand-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                :aria-pressed="role === 'editor'"
                                @click="role = 'editor'"
                            >
                                <Pencil class="size-3" />
                                {{ t('shareBoard.editor') }}
                            </button>
                            <button
                                type="button"
                                class="inline-flex h-7 items-center gap-1.5 rounded-sm px-2 text-xs font-medium transition-colors"
                                :class="
                                    role === 'viewer'
                                        ? 'bg-brand text-brand-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                :aria-pressed="role === 'viewer'"
                                @click="role = 'viewer'"
                            >
                                <Eye class="size-3" />
                                {{ t('shareBoard.viewer') }}
                            </button>
                        </div>
                    </div>
                </form>

                <div
                    v-if="members.length"
                    class="mt-6 overflow-hidden rounded-lg border border-border"
                >
                    <div
                        class="flex items-center justify-between border-b border-border bg-muted/30 px-3 py-2 text-xs font-medium text-muted-foreground"
                    >
                        <span>{{ t('shareBoard.members') }}</span>
                        <span>{{ members.length }}</span>
                    </div>
                    <div class="divide-y divide-border">
                        <div
                            v-for="member in members"
                            :key="`${member.pending ? 'invite' : 'user'}-${member.id}`"
                            class="group flex items-center gap-3 px-3 py-2.5 text-sm transition-colors hover:bg-muted/40"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        v-if="member.name"
                                        class="truncate font-medium text-foreground"
                                    >
                                        {{ member.name }}
                                    </span>
                                    <span
                                        v-else
                                        class="truncate font-medium text-muted-foreground"
                                    >
                                        {{ member.email }}
                                    </span>
                                    <Badge
                                        v-if="member.pending"
                                        variant="secondary"
                                        class="shrink-0 gap-1"
                                    >
                                        <MailCheck class="size-3" />
                                        {{ t('shareBoard.pending') }}
                                    </Badge>
                                </div>
                                <div
                                    v-if="member.name"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ member.email }}
                                </div>
                                <div
                                    v-else
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ t('shareBoard.pendingHint') }}
                                </div>
                            </div>

                            <!-- A pending row has no user to re-target, so the role is fixed until they join. -->
                            <span
                                v-if="member.pending"
                                class="shrink-0 rounded-md bg-muted px-2 py-1 text-xs font-medium text-muted-foreground capitalize"
                            >
                                {{
                                    member.role === 'editor'
                                        ? t('shareBoard.editor')
                                        : t('shareBoard.viewer')
                                }}
                            </span>
                            <div
                                v-else
                                class="inline-flex shrink-0 rounded-md border border-border bg-background p-0.5"
                                role="group"
                                :aria-label="`${member.email} role`"
                            >
                                <button
                                    type="button"
                                    class="inline-flex size-7 items-center justify-center rounded-sm transition-colors"
                                    :class="
                                        member.role === 'editor'
                                            ? 'bg-brand text-brand-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground'
                                    "
                                    :title="t('shareBoard.editor')"
                                    :aria-label="t('shareBoard.editor')"
                                    :aria-pressed="member.role === 'editor'"
                                    @click="updateRole(member, 'editor')"
                                >
                                    <Pencil class="size-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex size-7 items-center justify-center rounded-sm transition-colors"
                                    :class="
                                        member.role === 'viewer'
                                            ? 'bg-brand text-brand-foreground shadow-sm'
                                            : 'text-muted-foreground hover:text-foreground'
                                    "
                                    :title="t('shareBoard.viewer')"
                                    :aria-label="t('shareBoard.viewer')"
                                    :aria-pressed="member.role === 'viewer'"
                                    @click="updateRole(member, 'viewer')"
                                >
                                    <Eye class="size-3.5" />
                                </button>
                            </div>

                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-8 text-muted-foreground hover:bg-destructive/10 hover:text-destructive sm:opacity-0 sm:group-hover:opacity-100 sm:focus-visible:opacity-100"
                                :title="
                                    member.pending
                                        ? t('shareBoard.revokeInvite')
                                        : t('shareBoard.removeAccess')
                                "
                                @click="remove(member)"
                            >
                                <X class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
                <p
                    v-else
                    class="mt-6 rounded-lg border border-dashed border-border px-4 py-7 text-center text-sm text-muted-foreground"
                >
                    {{ t('shareBoard.noOneYet') }}
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
