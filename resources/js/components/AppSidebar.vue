<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Plus, Search, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
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

const page = usePage();
const boards = computed(() => page.props.sidebarBoards);
const { isCurrentUrl } = useCurrentUrl();

const commandOpen = ref(false);
const createOpen = ref(false);
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
                Search
                <span class="ml-auto text-[11px] text-sidebar-foreground/50">⌘K</span>
            </button>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Boards</SidebarGroupLabel>
                <SidebarGroupAction title="New board" @click="createOpen = true">
                    <Plus />
                </SidebarGroupAction>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child :is-active="isCurrentUrl('/boards')" tooltip="All boards">
                            <Link href="/boards">
                                <LayoutGrid />
                                <span>All boards</span>
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
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <CommandSearch v-model:open="commandOpen" />
    <CreateBoardDialog v-model:open="createOpen" />
</template>
