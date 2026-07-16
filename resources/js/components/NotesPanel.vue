<script setup lang="ts">
import { Pencil, Plus, StickyNote, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import NoteEditor from '@/components/NoteEditor.vue';
import { Button } from '@/components/ui/button';
import { request } from '@/lib/boardApi';
import type { Board, EditingTarget, Note } from '@/types/board';

const props = defineProps<{
    board: Board;
    isAuthenticated: boolean;
    canEdit: boolean;
    /** Supplied by the board channel; both no-op for guests. */
    editingLabelFor: (target: EditingTarget) => string | null;
    whisperEditing: (target: EditingTarget | null) => void;
}>();
const { board } = props;
const { t } = useI18n();

function noteEditingLabel(note: Note): string | null {
    return props.editingLabelFor(`note:${note.id}`);
}

type NoteNode = Note & { depth: number; children: NoteNode[] };

const activeNoteId = ref<string | null>(props.board.notes[0]?.id ?? null);

const activeNote = computed<Note | null>(
    () => props.board.notes.find((note) => note.id === activeNoteId.value) ?? null,
);

const noteTree = computed<NoteNode[]>(() => {
    const byParent = new Map<string | null, Note[]>();

    for (const note of props.board.notes) {
        const key = note.parent_id;
        const siblings = byParent.get(key) ?? [];
        siblings.push(note);
        byParent.set(key, siblings);
    }

    function build(parentId: string | null, depth: number): NoteNode[] {
        return (byParent.get(parentId) ?? []).map((note) => ({
            ...note,
            depth,
            children: build(note.id, depth + 1),
        }));
    }

    return build(null, 0);
});

function flatten(nodes: NoteNode[]): NoteNode[] {
    const result: NoteNode[] = [];

    for (const node of nodes) {
        result.push(node);
        result.push(...flatten(node.children));
    }

    return result;
}

const visibleNotes = computed(() => flatten(noteTree.value));

function selectNote(note: Note): void {
    activeNoteId.value = note.id;
}

async function createPage(parentId: string | null): Promise<void> {
    try {
        let note: Note;

        if (props.isAuthenticated) {
            note = await request<Note>(`/boards/${props.board.id}/notes`, 'POST', {
                parent_id: parentId,
            });
        } else {
            const now = new Date().toISOString();
            note = {
                id: crypto.randomUUID(),
                parent_id: parentId,
                title: '',
                body: '',
                created_at: now,
                updated_at: now,
            };
        }

        board.notes.unshift(note);
        activeNoteId.value = note.id;
    } catch {
        toast.error(t('board.toastCreateNoteError'));
    }
}

async function saveNote(note: Note): Promise<void> {
    if (!props.isAuthenticated) {
        return;
    }

    try {
        await request(`/notes/${note.id}`, 'PATCH', {
            title: note.title,
            body: note.body,
        });
    } catch {
        toast.error(t('board.toastSaveNoteError'));
    }
}

function collectWithDescendants(noteId: string): Set<string> {
    const ids = new Set<string>([noteId]);
    let added = true;

    while (added) {
        added = false;

        for (const note of props.board.notes) {
            if (note.parent_id && ids.has(note.parent_id) && !ids.has(note.id)) {
                ids.add(note.id);
                added = true;
            }
        }
    }

    return ids;
}

async function deleteNote(note: Note): Promise<void> {
    try {
        if (props.isAuthenticated) {
            await request(`/notes/${note.id}`, 'DELETE');
        }

        const idsToRemove = collectWithDescendants(note.id);
        board.notes = board.notes.filter(
            (item) => !idsToRemove.has(item.id),
        );

        if (activeNoteId.value && idsToRemove.has(activeNoteId.value)) {
            activeNoteId.value = props.board.notes[0]?.id ?? null;
        }
    } catch {
        toast.error(t('board.toastDeleteNoteError'));
    }
}
</script>

<template>
    <div v-if="board.notes.length === 0" class="flex flex-col items-center rounded-md border border-dashed border-border px-6 py-14 text-center">
        <div class="mb-3 flex size-10 items-center justify-center rounded-full bg-brand/10 text-brand">
            <StickyNote class="size-5" />
        </div>
        <p class="text-sm font-medium text-foreground">{{ t('board.noNotesYetTitle') }}</p>
        <p class="mt-1 text-xs text-muted-foreground">{{ t('board.noNotesYetSubtitle') }}</p>
        <Button v-if="canEdit" size="sm" class="mt-4 bg-brand text-brand-foreground hover:bg-brand/90" @click="createPage(null)">
            <Plus class="size-4" /> {{ t('board.newPage') }}
        </Button>
    </div>

    <div v-else class="flex flex-col gap-4 sm:flex-row">
        <div class="flex shrink-0 flex-col gap-0.5 sm:w-56">
            <Button v-if="canEdit" size="sm" variant="outline" class="mb-2 justify-start" @click="createPage(null)">
                <Plus class="size-4" /> {{ t('board.newPage') }}
            </Button>
            <div
                v-for="node in visibleNotes"
                :key="node.id"
                class="group flex items-center gap-1 rounded-md py-1.5 pr-1 text-sm"
                :class="node.id === activeNoteId ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent/50'"
                :style="{ paddingLeft: `${node.depth * 16 + 8}px` }"
            >
                <button
                    type="button"
                    class="min-w-0 flex-1 truncate text-left"
                    @click="selectNote(node)"
                >
                    {{ node.title || t('board.untitled') }}
                </button>
                <Pencil
                    v-if="noteEditingLabel(node)"
                    class="size-3 shrink-0 text-brand"
                    :title="t('realtime.editing', { name: noteEditingLabel(node) })"
                />
                <button
                    v-if="canEdit"
                    type="button"
                    class="flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground opacity-0 hover:text-foreground group-hover:opacity-100"
                    :title="t('board.addSubpage')"
                    @click="createPage(node.id)"
                >
                    <Plus class="size-3.5" />
                </button>
                <button
                    v-if="canEdit"
                    type="button"
                    class="flex size-6 shrink-0 items-center justify-center rounded text-muted-foreground opacity-0 hover:text-destructive group-hover:opacity-100"
                    :title="t('board.deletePage')"
                    @click="deleteNote(node)"
                >
                    <Trash2 class="size-3.5" />
                </button>
            </div>
        </div>

        <div v-if="activeNote" class="min-w-0 flex-1 rounded-md border border-border p-4">
            <p v-if="noteEditingLabel(activeNote)" class="mb-2 flex items-center gap-1 text-xs font-medium text-brand">
                <Pencil class="size-3" />
                {{ t('realtime.editing', { name: noteEditingLabel(activeNote) }) }}
            </p>
            <input
                :value="activeNote.title"
                :readonly="!canEdit"
                :placeholder="t('board.untitled')"
                class="mb-3 w-full bg-transparent text-lg font-semibold text-foreground outline-none placeholder:text-muted-foreground/60"
                @input="
                    activeNote.title = ($event.target as HTMLInputElement).value
                "
                @focus="whisperEditing(`note:${activeNote.id}`)"
                @blur="
                    whisperEditing(null);
                    saveNote(activeNote);
                "
            />
            <NoteEditor
                v-model="activeNote.body"
                :editable="canEdit"
                :image-upload-url="isAuthenticated ? `/notes/${activeNote.id}/images` : undefined"
                @focus="whisperEditing(`note:${activeNote.id}`)"
                @blur="whisperEditing(null)"
                @save="saveNote(activeNote)"
            />
        </div>
    </div>
</template>
