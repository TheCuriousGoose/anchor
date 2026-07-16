export type Priority = 'low' | 'medium' | 'high' | null;
export type BoardRole = 'owner' | 'editor' | 'viewer';
export type CollaboratorRole = 'editor' | 'viewer';
export type LabelColor =
    | 'red'
    | 'orange'
    | 'amber'
    | 'green'
    | 'teal'
    | 'blue'
    | 'purple'
    | 'pink'
    | 'gray';

export type Label = {
    id: string;
    name: string;
    color: LabelColor;
};

export type Task = {
    id: string;
    title: string;
    description: string | null;
    completed: boolean;
    position: number;
    priority: Priority;
    due_date: string | null;
    labels: Label[];
};

export type Note = {
    id: string;
    parent_id: string | null;
    title: string;
    body: string;
    created_at: string;
    updated_at: string;
};

export type Collaborator = {
    /** A user id when pending is false; a board_invitations id when it is true. */
    id: number;
    name: string | null;
    email: string;
    role: CollaboratorRole;
    pending: boolean;
};

export type Board = {
    id: string;
    name: string;
    icon: string;
    tasks: Task[];
    notes: Note[];
    labels: Label[];
    isOwner: boolean;
    role: BoardRole;
    collaborators: Collaborator[];
    /** Shared with an address that has no account yet. Owner-only; empty for everyone else. */
    invitations: Collaborator[];
};

export type SidebarBoard = {
    id: string;
    name: string;
    icon: string;
    openTasksCount: number;
    isOwner: boolean;
    role: BoardRole;
};

/** Someone currently subscribed to a board's presence channel. Shape set by routes/channels.php. */
export type PresenceMember = {
    id: number;
    name: string;
    avatar: string | null;
    role: BoardRole;
};

/** Whoever performed a broadcast mutation, used for "Ada added a task" style messaging. */
export type Actor = { id: number; name: string } | null;

/**
 * A thing someone can be editing. Namespaced because task and note ids are both UUIDs and
 * would otherwise collide in the same lookup.
 */
export type EditingTarget = `task:${string}` | `note:${string}`;

/** Payload of the `editing` whisper used for live "X is editing…" hints. */
export type EditingWhisper = {
    id: number;
    name: string;
    target: EditingTarget | null;
};
