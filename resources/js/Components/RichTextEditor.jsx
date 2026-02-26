import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import { useEffect } from 'react';

function ToolbarButton({ active = false, disabled = false, onClick, title, children }) {
    return (
        <button
            type="button"
            className={`inline-flex items-center rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset transition ${
                disabled
                    ? 'opacity-50 cursor-not-allowed ring-slate-200 text-slate-400'
                    : active
                      ? 'bg-amber-50 ring-amber-200 text-amber-900'
                      : 'bg-white ring-slate-200 text-slate-700 hover:bg-slate-50'
            }`}
            onClick={onClick}
            disabled={disabled}
            aria-pressed={active}
            title={title}
        >
            {children}
        </button>
    );
}

export default function RichTextEditor({ value = '', onChange, placeholder = 'Write here…' }) {
    const editor = useEditor({
        extensions: [
            StarterKit,
            Underline,
            Placeholder.configure({
                placeholder,
            }),
        ],
        content: value || '',
        editorProps: {
            attributes: {
                class: 'min-h-[280px] p-3 text-sm text-slate-900 outline-none',
            },
        },
        onUpdate({ editor }) {
            const html = editor.getHTML();
            const normalized = html === '<p></p>' ? '' : html;
            onChange?.(normalized);
        },
    });

    useEffect(() => {
        if (!editor) return;

        const next = value || '';
        const current = editor.getHTML();

        if (next === '' && current === '<p></p>') return;
        if (current === next) return;

        editor.commands.setContent(next, false);
    }, [value, editor]);

    if (!editor) {
        return (
            <div className="rounded-md border border-slate-300 bg-white">
                <div className="border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">Loading editor…</div>
                <div className="min-h-[280px] p-3" />
            </div>
        );
    }

    return (
        <div className="rounded-md border border-slate-300 bg-white focus-within:border-amber-500 focus-within:ring-1 focus-within:ring-amber-500">
            <div className="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 px-2 py-2">
                <ToolbarButton
                    title="Bold"
                    active={editor.isActive('bold')}
                    disabled={!editor.can().chain().focus().toggleBold().run()}
                    onClick={() => editor.chain().focus().toggleBold().run()}
                >
                    B
                </ToolbarButton>
                <ToolbarButton
                    title="Italic"
                    active={editor.isActive('italic')}
                    disabled={!editor.can().chain().focus().toggleItalic().run()}
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                >
                    I
                </ToolbarButton>
                <ToolbarButton
                    title="Underline"
                    active={editor.isActive('underline')}
                    disabled={!editor.can().chain().focus().toggleUnderline().run()}
                    onClick={() => editor.chain().focus().toggleUnderline().run()}
                >
                    U
                </ToolbarButton>

                <div className="mx-1 h-4 w-px bg-slate-200" />

                <ToolbarButton
                    title="Bullet list"
                    active={editor.isActive('bulletList')}
                    onClick={() => editor.chain().focus().toggleBulletList().run()}
                >
                    • List
                </ToolbarButton>
                <ToolbarButton
                    title="Numbered list"
                    active={editor.isActive('orderedList')}
                    onClick={() => editor.chain().focus().toggleOrderedList().run()}
                >
                    1. List
                </ToolbarButton>
                <ToolbarButton
                    title="Quote"
                    active={editor.isActive('blockquote')}
                    onClick={() => editor.chain().focus().toggleBlockquote().run()}
                >
                    “ ”
                </ToolbarButton>

                <div className="mx-1 h-4 w-px bg-slate-200" />

                <ToolbarButton title="Undo" disabled={!editor.can().chain().focus().undo().run()} onClick={() => editor.chain().focus().undo().run()}>
                    Undo
                </ToolbarButton>
                <ToolbarButton title="Redo" disabled={!editor.can().chain().focus().redo().run()} onClick={() => editor.chain().focus().redo().run()}>
                    Redo
                </ToolbarButton>
            </div>

            <EditorContent editor={editor} />
        </div>
    );
}
