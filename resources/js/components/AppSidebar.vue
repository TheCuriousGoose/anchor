<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ChartColumn, LayoutGrid, Plus, ScrollText, Search, Shield, Users, UsersRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogo from '@/components/AppLogo.vue';
import BoardIcon from '@/components/BoardIcon.vue';
import CommandSearch from '@/components/CommandSearch.vue';
import CreateBoardDialog from '@/components/CreateBoardDialog.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupAction,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useUserChannel } from '@/composables/useUserChannel';

const page = usePage();
const boards = computed(() => page.props.sidebarBoards);
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const { isCurrentUrl } = useCurrentUrl();
const { t } = useI18n();

const commandOpen = ref(false);
const createOpen = ref(false);

// The sidebar is on every authenticated page, so this is where sharing changes are picked
// up — the board list stays live even when you're nowhere near the board in question.
useUserChannel();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/boards">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <button type="button"
                class="mx-2 mt-1 flex h-9 items-center gap-2 rounded-md px-2 text-sm text-sidebar-foreground/70 group-data-[collapsible=icon]:hidden hover:bg-sidebar-accent"
                @click="commandOpen = true">
                <Search class="size-4" />
                {{ t('sidebar.search') }}
                <span class="ml-auto text-[11px] text-sidebar-foreground/50">⌘K</span>
            </button>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>{{ t('sidebar.boards') }}</SidebarGroupLabel>
                <SidebarGroupAction :title="t('sidebar.newBoard')" @click="createOpen = true">
                    <Plus />
                </SidebarGroupAction>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/boards')" :tooltip="t('sidebar.allBoards')">
                            <Link href="/boards">
                                <LayoutGrid />
                                <span>{{ t('sidebar.allBoards') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem v-for="board in boards" :key="board.id">
                        <SidebarMenuButton as-child :is-active="isCurrentUrl(`/boards/${board.id}`)"
                            :tooltip="board.name">
                            <Link :href="`/boards/${board.id}`">
                                <BoardIcon :icon="board.icon" class="size-4" />
                                <span class="min-w-0 flex-1 truncate">{{
                                    board.name
                                    }}</span>
                                <Users v-if="!board.isOwner" class="size-3 shrink-0 opacity-50" />
                            </Link>
                        </SidebarMenuButton>
                        <SidebarMenuBadge v-if="board.openTasksCount > 0">{{
                            board.openTasksCount
                            }}</SidebarMenuBadge>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup v-if="isAdmin">
                <SidebarGroupLabel>
                    <Shield class="mr-1.5 size-3.5" />
                    {{ t('sidebar.admin') }}
                </SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/admin')" :tooltip="t('sidebar.adminOverview')">
                            <Link href="/admin">
                                <ChartColumn />
                                <span>{{ t('sidebar.adminOverview') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/admin/users')" :tooltip="t('sidebar.adminUsers')">
                            <Link href="/admin/users">
                                <UsersRound />
                                <span>{{ t('sidebar.adminUsers') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/admin/boards')" :tooltip="t('sidebar.adminBoards')">
                            <Link href="/admin/boards">
                                <LayoutGrid />
                                <span>{{ t('sidebar.adminBoards') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/admin/audit')" :tooltip="t('sidebar.adminAudit')">
                            <Link href="/admin/audit">
                                <ScrollText />
                                <span>{{ t('sidebar.adminAudit') }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <CommandSearch v-model:open="commandOpen" />
    <CreateBoardDialog v-model:open="createOpen" />
</template>
