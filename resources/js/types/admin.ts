import type { UserRole } from './auth';

/** Laravel's LengthAwarePaginator, as Inertia serializes it. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type AdminUser = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    role: UserRole;
    suspended: boolean;
    boardsCount: number;
    createdAt: string | null;
};

export type AdminBoardOwner = {
    id: number;
    name: string;
    email: string;
};

export type AdminBoard = {
    id: string;
    name: string;
    icon: string;
    owner: AdminBoardOwner;
    tasksCount: number;
    notesCount: number;
    collaboratorsCount: number;
    createdAt: string | null;
};

export type AdminBoardDetail = {
    id: string;
    name: string;
    icon: string;
    owner: AdminBoardOwner;
    createdAt: string | null;
    tasks: { id: string; title: string; completed: boolean; priority: string | null }[];
    notes: { id: string; title: string }[];
    collaborators: { id: number; name: string; email: string; role: string }[];
};

export type AuditLogEntry = {
    id: number;
    actor: string;
    actorId: number | null;
    action: string;
    targetType: string | null;
    targetLabel: string | null;
    metadata: Record<string, unknown> | null;
    ipAddress: string | null;
    createdAt: string | null;
};

/** The trimmed audit shape the metrics dashboard renders. */
export type RecentActivity = {
    id: number;
    actor: string;
    action: string;
    target: string | null;
    createdAt: string | null;
};

export type MetricTotals = {
    users: number;
    admins: number;
    suspended: number;
    boards: number;
    tasks: number;
    openTasks: number;
    notes: number;
};

export type DailyCount = {
    date: string;
    count: number;
};
