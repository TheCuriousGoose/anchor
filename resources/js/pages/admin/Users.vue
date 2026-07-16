<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search, ShieldCheck, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';

import AdminPagination from '@/components/AdminPagination.vue';
import DeleteUserDialog from '@/components/DeleteUserDialog.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem, User, UserRole } from '@/types';
import type { AdminUser, Paginated } from '@/types/admin';

const props = defineProps<{
    users: Paginated<AdminUser>;
    filters: { search: string };
}>();

const { t } = useI18n();
const { getInitials } = useInitials();
const page = usePage();

const search = ref(props.filters.search);
const currentUserId = (page.props.auth.user as User).id;

let searchTimeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/users',
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

function changeRole(user: AdminUser, role: UserRole): void {
    const previous = user.role;
    user.role = role;

    router.patch(
        `/admin/users/${user.id}/role`,
        { role },
        {
            preserveScroll: true,
            onError: () => {
                user.role = previous;
                toast.error(t('admin.users.roleError'));
            },
        },
    );
}

function toggleSuspension(user: AdminUser): void {
    const suspended = !user.suspended;
    user.suspended = suspended;

    router.patch(
        `/admin/users/${user.id}/suspension`,
        { suspended },
        {
            preserveScroll: true,
            onError: () => {
                user.suspended = !suspended;
                toast.error(t('admin.users.suspendError'));
            },
        },
    );
}

function deleteUser(user: AdminUser): void {
    router.delete(`/admin/users/${user.id}`, {
        preserveScroll: true,
        onError: () => toast.error(t('admin.users.deleteError')),
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: '/admin' },
            { title: 'Users', href: '/admin/users' },
        ] satisfies BreadcrumbItem[],
    },
});
</script>

<template>
    <Head :title="t('admin.users.title')" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-1">
            <h1 class="text-2xl font-semibold tracking-tight">{{ t('admin.users.title') }}</h1>
            <p class="text-sm text-muted-foreground">{{ t('admin.users.subtitle', { count: users.total }) }}</p>
        </div>

        <div class="relative max-w-sm">
            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" :placeholder="t('admin.users.searchPlaceholder')" class="pl-9" />
        </div>

        <div class="overflow-hidden rounded-lg border border-border">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>{{ t('admin.users.columnUser') }}</TableHead>
                            <TableHead>{{ t('admin.users.columnRole') }}</TableHead>
                            <TableHead>{{ t('admin.users.columnStatus') }}</TableHead>
                            <TableHead class="text-right">{{ t('admin.users.columnBoards') }}</TableHead>
                            <TableHead class="w-0"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="user in users.data" :key="user.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <Avatar class="size-8">
                                        <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                        <AvatarFallback class="text-xs">{{ getInitials(user.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate font-medium">{{ user.name }}</span>
                                            <Badge v-if="user.id === currentUserId" variant="outline" class="text-xs">
                                                {{ t('admin.users.you') }}
                                            </Badge>
                                        </div>
                                        <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Select
                                    :model-value="user.role"
                                    :disabled="user.id === currentUserId"
                                    @update:model-value="(role) => changeRole(user, role as UserRole)"
                                >
                                    <SelectTrigger size="sm" class="w-28">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="user">{{ t('admin.users.roleUser') }}</SelectItem>
                                        <SelectItem value="admin">{{ t('admin.users.roleAdmin') }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="user.suspended ? 'destructive' : 'secondary'">
                                    {{ user.suspended ? t('admin.users.suspended') : t('admin.users.active') }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right tabular-nums">{{ user.boardsCount }}</TableCell>
                            <TableCell>
                                <div class="flex items-center justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :disabled="user.id === currentUserId"
                                        @click="toggleSuspension(user)"
                                    >
                                        <ShieldCheck class="size-4" />
                                        {{ user.suspended ? t('admin.users.unsuspend') : t('admin.users.suspend') }}
                                    </Button>
                                    <DeleteUserDialog
                                        v-if="user.id !== currentUserId"
                                        :user="user"
                                        @confirm="deleteUser(user)"
                                    >
                                        <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive">
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </DeleteUserDialog>
                                </div>
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="users.data.length === 0">
                            <TableCell colspan="5" class="py-10 text-center text-sm text-muted-foreground">
                                {{ t('admin.users.empty') }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <AdminPagination :paginated="users" />
    </div>
</template>
