<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { getInitials } from '@/composables/useInitials';
import type { PresenceMember } from '@/types/board';

const props = defineProps<{
    members: PresenceMember[];
    currentUserId: number;
}>();
const { t } = useI18n();

/** Cap the stack so a busy board doesn't push the board menu off-screen. */
const maxVisible = 4;

// The local user first, then everyone else, so the stack doesn't reshuffle as people
// join and leave around them.
const ordered = computed<PresenceMember[]>(() => [
    ...props.members.filter((member) => member.id === props.currentUserId),
    ...props.members.filter((member) => member.id !== props.currentUserId),
]);

const visible = computed(() => ordered.value.slice(0, maxVisible));
const overflow = computed(() => Math.max(0, ordered.value.length - maxVisible));

function labelFor(member: PresenceMember): string {
    return member.id === props.currentUserId
        ? `${member.name} (${t('realtime.you')})`
        : member.name;
}

const summary = computed(() =>
    ordered.value.length <= 1
        ? t('realtime.viewersOne')
        : t('realtime.viewersOther', { count: ordered.value.length }),
);
</script>

<template>
    <!-- Nothing to show until the roster arrives, and a solo viewer isn't "collaboration". -->
    <div v-if="members.length > 1" class="flex items-center" :title="summary">
        <TooltipProvider :delay-duration="200">
            <div class="flex -space-x-2">
                <Tooltip v-for="member in visible" :key="member.id">
                    <TooltipTrigger as-child>
                        <Avatar
                            class="size-7 ring-2 ring-background transition-transform hover:z-10 hover:scale-110"
                        >
                            <AvatarImage
                                v-if="member.avatar"
                                :src="member.avatar"
                                :alt="member.name"
                            />
                            <AvatarFallback
                                class="bg-brand/10 text-[10px] font-medium text-brand"
                            >
                                {{ getInitials(member.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </TooltipTrigger>
                    <TooltipContent>{{ labelFor(member) }}</TooltipContent>
                </Tooltip>
            </div>
        </TooltipProvider>

        <span
            v-if="overflow > 0"
            class="ml-1 text-xs font-medium text-muted-foreground"
        >
            +{{ overflow }}
        </span>
    </div>
</template>
