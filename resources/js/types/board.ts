export type Priority = 'low' | 'medium' | 'high' | null;
export type BoardRole = 'owner' | 'editor' | 'viewer';
export type CollaboratorRole = 'editor' | 'viewer';

export type Task = {
    id: string;
    title: string;
    completed: boolean;
    position: number;
    priority: Priority;
};

export type Note = {
    id: string;
    title: string;
    body: string;
    created_at: string;
    updated_at: string;
};

export type Collaborator = {
    id: number;
    name: string;
    email: string;
    role: CollaboratorRole;
};

export type Board = {
    id: string;
    name: string;
    icon: string;
    tasks: Task[];
    notes: Note[];
    isOwner: boolean;
    role: BoardRole;
    collaborators: Collaborator[];
};

export type SidebarBoard = {
    id: string;
    name: string;
    icon: string;
    openTasksCount: number;
    isOwner: boolean;
    role: BoardRole;
};
