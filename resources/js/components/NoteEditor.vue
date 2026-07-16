<script setup lang="ts">
import {
    Bold,
    Heading2,
    Heading3,
    Image as ImageIcon,
    Italic,
    List,
    ListChecks,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    SquareCode,
    Strikethrough,
    Undo2,
} from '@lucide/vue';
import type { Editor } from '@tiptap/core';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { useDebounceFn } from '@vueuse/core';
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { apiHeaders } from '@/lib/boardApi';

const props = defineProps<{
    modelValue: string;
    editable: boolean;
    placeholder?: string;
    imageUploadUrl?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
    save: [];
    focus: [];
    blur: [];
}>();

const { t } = useI18n();
const imageInput = ref<HTMLInputElement | null>(null);

function triggerImagePick(): void {
    imageInput.value?.click();
}

async function uploadImage(file: File): Promise<void> {
    if (!props.imageUploadUrl || !editor.value) {
        return;
    }

    const formData = new FormData();
    formData.append('image', file);

    try {
        const response = await fetch(props.imageUploadUrl, {
            method: 'POST',
            // No Content-Type: the browser sets the multipart boundary itself.
            headers: apiHeaders(),
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Upload failed with status ${response.status}`);
        }

        const { url } = (await response.json()) as { url: string };
        editor.value.chain().focus().setImage({ src: url }).run();
        emit('save');
    } catch {
        toast.error(t('noteEditor.uploadImageError'));
    }
}

function handleImageInputChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    (event.target as HTMLInputElement).value = '';

    if (file) {
        void uploadImage(file);
    }
}

const debouncedSave = useDebounceFn(() => emit('save'), 800);

const editorWrapper = ref<HTMLElement | null>(null);

type SlashItem = {
    id: string;
    label: string;
    icon: unknown;
    command: (editor: Editor) => void;
};

const slashItems = computed<SlashItem[]>(() => [
    {
        id: 'heading2',
        label: t('noteEditor.slashHeading2'),
        icon: Heading2,
        command: (editor) =>
            editor.chain().focus().setNode('heading', { level: 2 }).run(),
    },
    {
        id: 'heading3',
        label: t('noteEditor.slashHeading3'),
        icon: Heading3,
        command: (editor) =>
            editor.chain().focus().setNode('heading', { level: 3 }).run(),
    },
    {
        id: 'bulletList',
        label: t('noteEditor.slashBulletList'),
        icon: List,
        command: (editor) => editor.chain().focus().toggleBulletList().run(),
    },
    {
        id: 'orderedList',
        label: t('noteEditor.slashOrderedList'),
        icon: ListOrdered,
        command: (editor) => editor.chain().focus().toggleOrderedList().run(),
    },
    {
        id: 'checklist',
        label: t('noteEditor.slashChecklist'),
        icon: ListChecks,
        command: (editor) => editor.chain().focus().toggleTaskList().run(),
    },
    {
        id: 'quote',
        label: t('noteEditor.slashQuote'),
        icon: Quote,
        command: (editor) => editor.chain().focus().toggleBlockquote().run(),
    },
    {
        id: 'codeBlock',
        label: t('noteEditor.slashCodeBlock'),
        icon: SquareCode,
        command: (editor) => editor.chain().focus().toggleCodeBlock().run(),
    },
    {
        id: 'divider',
        label: t('noteEditor.slashDivider'),
        icon: Minus,
        command: (editor) => editor.chain().focus().setHorizontalRule().run(),
    },
    ...(props.imageUploadUrl
        ? [
              {
                  id: 'image',
                  label: t('noteEditor.slashImage'),
                  icon: ImageIcon,
                  command: () => triggerImagePick(),
              } satisfies SlashItem,
          ]
        : []),
]);

const slashOpen = ref(false);
const slashQuery = ref('');
const slashRange = ref<{ from: number; to: number } | null>(null);
const slashSelectedIndex = ref(0);
const slashMenuStyle = ref({ top: '0px', left: '0px' });

const filteredSlashItems = computed(() => {
    const query = slashQuery.value.trim().toLowerCase();

    if (!query) {
        return slashItems.value;
    }

    return slashItems.value.filter((item) =>
        item.label.toLowerCase().includes(query),
    );
});

function closeSlashMenu(): void {
    slashOpen.value = false;
    slashQuery.value = '';
    slashRange.value = null;
    slashSelectedIndex.value = 0;
}

function updateSlashMenuPosition(): void {
    if (!editor.value || !editorWrapper.value) {
        return;
    }

    const { from } = editor.value.state.selection;
    const coords = editor.value.view.coordsAtPos(from);
    const wrapperRect = editorWrapper.value.getBoundingClientRect();

    slashMenuStyle.value = {
        top: `${coords.bottom - wrapperRect.top + 4}px`,
        left: `${coords.left - wrapperRect.left}px`,
    };
}

function checkSlashTrigger(): void {
    if (!editor.value) {
        return;
    }

    const { $from } = editor.value.state.selection;
    const textBefore = $from.parent.textBetween(
        0,
        $from.parentOffset,
        undefined,
        '￼',
    );
    const match = /^\/(\w*)$/.exec(textBefore);

    if (!match) {
        closeSlashMenu();

        return;
    }

    slashQuery.value = match[1];
    slashRange.value = { from: $from.pos - match[0].length, to: $from.pos };
    slashSelectedIndex.value = 0;
    slashOpen.value = true;
    void nextTick(updateSlashMenuPosition);
}

function selectSlashItem(item: SlashItem): void {
    const range = slashRange.value;

    if (!editor.value || !range) {
        return;
    }

    editor.value.chain().focus().deleteRange(range).run();
    item.command(editor.value);
    closeSlashMenu();
}

const editor = useEditor({
    content: props.modelValue,
    editable: props.editable,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Placeholder.configure({
            placeholder: props.placeholder ?? t('noteEditor.placeholder'),
        }),
        TaskList,
        TaskItem.configure({ nested: true }),
        Image,
    ],
    editorProps: {
        attributes: {
            class: 'note-editor-content prose-sm max-w-none focus:outline-none',
        },
        handleKeyDown: (_view, event) => {
            if (!slashOpen.value || filteredSlashItems.value.length === 0) {
                return false;
            }

            if (event.key === 'ArrowDown') {
                slashSelectedIndex.value =
                    (slashSelectedIndex.value + 1) %
                    filteredSlashItems.value.length;

                return true;
            }

            if (event.key === 'ArrowUp') {
                slashSelectedIndex.value =
                    (slashSelectedIndex.value - 1 + filteredSlashItems.value.length) %
                    filteredSlashItems.value.length;

                return true;
            }

            if (event.key === 'Enter' || event.key === 'Tab') {
                const item = filteredSlashItems.value[slashSelectedIndex.value];

                if (item) {
                    selectSlashItem(item);
                }

                return true;
            }

            if (event.key === 'Escape') {
                closeSlashMenu();

                return true;
            }

            return false;
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
        checkSlashTrigger();
        debouncedSave();
    },
    onSelectionUpdate: () => {
        checkSlashTrigger();
    },
    onFocus: () => {
        emit('focus');
    },
    onBlur: () => {
        closeSlashMenu();
        emit('save');
        emit('blur');
    },
});

watch(
    () => props.editable,
    (editable) => editor.value?.setEditable(editable),
);

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value, { emitUpdate: false });
        }
    },
);

const toolbarActions = computed(() => [
    {
        label: t('noteEditor.toolbarBold'),
        icon: Bold,
        isActive: () => editor.value?.isActive('bold'),
        run: () => editor.value?.chain().focus().toggleBold().run(),
    },
    {
        label: t('noteEditor.toolbarItalic'),
        icon: Italic,
        isActive: () => editor.value?.isActive('italic'),
        run: () => editor.value?.chain().focus().toggleItalic().run(),
    },
    {
        label: t('noteEditor.toolbarStrike'),
        icon: Strikethrough,
        isActive: () => editor.value?.isActive('strike'),
        run: () => editor.value?.chain().focus().toggleStrike().run(),
    },
    {
        label: t('noteEditor.toolbarBulletList'),
        icon: List,
        isActive: () => editor.value?.isActive('bulletList'),
        run: () => editor.value?.chain().focus().toggleBulletList().run(),
    },
    {
        label: t('noteEditor.toolbarOrderedList'),
        icon: ListOrdered,
        isActive: () => editor.value?.isActive('orderedList'),
        run: () => editor.value?.chain().focus().toggleOrderedList().run(),
    },
    {
        label: t('noteEditor.toolbarChecklist'),
        icon: ListChecks,
        isActive: () => editor.value?.isActive('taskList'),
        run: () => editor.value?.chain().focus().toggleTaskList().run(),
    },
    {
        label: t('noteEditor.toolbarQuote'),
        icon: Quote,
        isActive: () => editor.value?.isActive('blockquote'),
        run: () => editor.value?.chain().focus().toggleBlockquote().run(),
    },
    ...(props.imageUploadUrl
        ? [
              {
                  label: t('noteEditor.toolbarImage'),
                  icon: ImageIcon,
                  isActive: () => false,
                  run: () => triggerImagePick(),
              },
          ]
        : []),
]);
</script>

<template>
    <div ref="editorWrapper" class="relative">
        <div
            v-if="editable"
            class="mb-2 flex flex-wrap items-center gap-0.5 border-b border-border pb-2"
        >
            <button
                v-for="action in toolbarActions"
                :key="action.label"
                type="button"
                class="flex size-7 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                :class="
                    action.isActive()
                        ? 'bg-brand/10 text-brand'
                        : ''
                "
                :title="action.label"
                @mousedown.prevent
                @click="action.run()"
            >
                <component :is="action.icon" class="size-3.5" />
            </button>
            <span class="mx-1 h-4 w-px bg-border" />
            <button
                type="button"
                class="flex size-7 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-30"
                :title="t('noteEditor.toolbarUndo')"
                :disabled="!editor?.can().undo()"
                @mousedown.prevent
                @click="editor?.chain().focus().undo().run()"
            >
                <Undo2 class="size-3.5" />
            </button>
            <button
                type="button"
                class="flex size-7 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-30"
                :title="t('noteEditor.toolbarRedo')"
                :disabled="!editor?.can().redo()"
                @mousedown.prevent
                @click="editor?.chain().focus().redo().run()"
            >
                <Redo2 class="size-3.5" />
            </button>
        </div>
        <EditorContent :editor="editor" />

        <input
            v-if="imageUploadUrl"
            ref="imageInput"
            type="file"
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="hidden"
            @change="handleImageInputChange"
        />

        <div
            v-if="slashOpen && filteredSlashItems.length > 0"
            class="absolute z-20 w-48 overflow-hidden rounded-md border border-border bg-popover py-1 shadow-md"
            :style="slashMenuStyle"
        >
            <button
                v-for="(item, index) in filteredSlashItems"
                :key="item.id"
                type="button"
                class="flex w-full items-center gap-2 px-2 py-1.5 text-left text-sm transition-colors"
                :class="
                    index === slashSelectedIndex
                        ? 'bg-accent text-accent-foreground'
                        : 'text-foreground hover:bg-accent hover:text-accent-foreground'
                "
                @mousedown.prevent="selectSlashItem(item)"
                @mouseenter="slashSelectedIndex = index"
            >
                <component :is="item.icon" class="size-3.5 shrink-0 text-muted-foreground" />
                {{ item.label }}
            </button>
        </div>
    </div>
</template>

<style>
.note-editor-content ul[data-type='taskList'] {
    list-style: none;
    padding-left: 0;
}

.note-editor-content ul[data-type='taskList'] li {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
}

.note-editor-content ul[data-type='taskList'] li > label {
    margin-top: 0.2rem;
    user-select: none;
}

.note-editor-content ul[data-type='taskList'] li > div {
    flex: 1;
}

.note-editor-content ul[data-type='taskList'] li[data-checked='true'] > div {
    color: var(--muted-foreground);
    text-decoration: line-through;
}

.note-editor-content ul:not([data-type='taskList']) {
    list-style: disc;
    padding-left: 1.25rem;
}

.note-editor-content ol {
    list-style: decimal;
    padding-left: 1.25rem;
}

.note-editor-content blockquote {
    border-left: 2px solid var(--border);
    padding-left: 0.75rem;
    color: var(--muted-foreground);
}

.note-editor-content pre {
    background: var(--muted);
    border-radius: 0.375rem;
    padding: 0.6rem 0.8rem;
    font-family: ui-monospace, monospace;
    font-size: 0.8rem;
    overflow-x: auto;
}

.note-editor-content {
    min-height: 12rem;
}

.note-editor-content img {
    max-width: 100%;
    border-radius: 0.375rem;
    margin: 0.5rem 0;
}

.note-editor-content hr {
    border: none;
    border-top: 1px solid var(--border);
    margin: 0.75rem 0;
}

.note-editor-content h2 {
    font-size: 1.125rem;
    font-weight: 600;
}

.note-editor-content h3 {
    font-size: 1rem;
    font-weight: 600;
}

.note-editor-content p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
    color: var(--muted-foreground);
    opacity: 0.6;
}
</style>
