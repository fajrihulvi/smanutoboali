import Image from '@tiptap/extension-image';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    ImagePlus,
    Italic,
    Link2,
    List,
    ListOrdered,
    Minus,
    Quote,
    Redo2,
    Strikethrough,
    Underline,
    Undo2,
    Unlink,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import { Spinner } from '@/Components/ui/spinner';
import { HttpError, sendJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import { PROSE_CLASS } from '@/Modules/Cms/prose';

type Props = {
    id?: string;
    value: string;
    onChange: (html: string) => void;
    invalid?: boolean;
};

const IMAGE_UPLOAD_URL = '/operator/cms/gambar-editor';
const IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

type ToolButtonProps = {
    icon: LucideIcon;
    label: string;
    active?: boolean;
    disabled?: boolean;
    onClick: () => void;
};

function ToolButton({
    icon: Icon,
    label,
    active,
    disabled,
    onClick,
}: ToolButtonProps) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={label}
            aria-pressed={active}
            title={label}
            className={cn(
                'inline-flex size-8 items-center justify-center rounded-md text-slate-600 transition-colors duration-150 hover:bg-slate-100 hover:text-slate-900 disabled:opacity-40',
                active && 'bg-brand-50 text-brand-700 hover:bg-brand-100',
            )}
        >
            <Icon className="size-4" />
        </button>
    );
}

function Divider() {
    return <span className="mx-1 h-5 w-px bg-slate-200" aria-hidden />;
}

/**
 * Editor rich text (TipTap). HTML keluaran tetap disanitasi server sebelum disimpan.
 * Gambar diunggah ke folder publik website lalu disisipkan sebagai <img>.
 */
export function RichTextEditor({ id, value, onChange, invalid }: Props) {
    const fileInput = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);

    const editor = useEditor({
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                code: false,
                codeBlock: false,
                link: {
                    openOnClick: false,
                    autolink: true,
                    protocols: ['mailto', 'tel'],
                    HTMLAttributes: { rel: 'noopener noreferrer' },
                },
            }),
            Image.configure({ HTMLAttributes: { loading: 'lazy' } }),
        ],
        content: value,
        immediatelyRender: false,
        editorProps: {
            attributes: {
                ...(id ? { id } : {}),
                'aria-label': 'Isi',
                class: cn(PROSE_CLASS, 'min-h-72 px-4 py-3 outline-none'),
            },
        },
        onUpdate: ({ editor: current }) =>
            onChange(current.isEmpty ? '' : current.getHTML()),
    });

    const state = useEditorState({
        editor,
        selector: ({ editor: e }) =>
            e
                ? {
                      bold: e.isActive('bold'),
                      italic: e.isActive('italic'),
                      underline: e.isActive('underline'),
                      strike: e.isActive('strike'),
                      h2: e.isActive('heading', { level: 2 }),
                      h3: e.isActive('heading', { level: 3 }),
                      bullet: e.isActive('bulletList'),
                      ordered: e.isActive('orderedList'),
                      quote: e.isActive('blockquote'),
                      link: e.isActive('link'),
                      canUndo: e.can().undo(),
                      canRedo: e.can().redo(),
                  }
                : null,
    });

    const setLink = () => {
        if (!editor) {
            return;
        }

        const previous = (editor.getAttributes('link').href as string) ?? '';
        const url = window.prompt('Alamat tautan (https://…)', previous);

        if (url === null) {
            return;
        }

        if (url.trim() === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();

            return;
        }

        const href = /^(https?:|mailto:|tel:|\/)/i.test(url.trim())
            ? url.trim()
            : `https://${url.trim()}`;
        editor
            .chain()
            .focus()
            .extendMarkRange('link')
            .setLink({ href, target: '_blank' })
            .run();
    };

    const uploadImage = async (file: File) => {
        if (!editor) {
            return;
        }

        if (!IMAGE_TYPES.includes(file.type)) {
            toast.error('Gunakan gambar JPG, PNG, atau WebP.');

            return;
        }

        const body = new FormData();
        body.append('image', file);
        setUploading(true);

        try {
            const result = await sendJson<{ url: string }>(
                IMAGE_UPLOAD_URL,
                'POST',
                body,
            );
            editor
                .chain()
                .focus()
                .setImage({
                    src: result.url,
                    alt: file.name.replace(/\.[^.]+$/, ''),
                })
                .run();
        } catch (error) {
            toast.error(
                error instanceof HttpError
                    ? (Object.values(error.errors)[0] ?? error.message)
                    : 'Gambar gagal diunggah.',
            );
        } finally {
            setUploading(false);
        }
    };

    const chain = () => editor?.chain().focus();

    return (
        <div
            className={cn(
                'overflow-hidden rounded-xl border bg-white shadow-xs focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50',
                invalid ? 'border-rose-400' : 'border-input',
            )}
        >
            <div
                role="toolbar"
                aria-label="Format teks"
                className="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50 p-1"
            >
                <ToolButton
                    icon={Heading2}
                    label="Judul bagian"
                    active={state?.h2}
                    onClick={() => chain()?.toggleHeading({ level: 2 }).run()}
                />
                <ToolButton
                    icon={Heading3}
                    label="Subjudul"
                    active={state?.h3}
                    onClick={() => chain()?.toggleHeading({ level: 3 }).run()}
                />
                <Divider />
                <ToolButton
                    icon={Bold}
                    label="Tebal"
                    active={state?.bold}
                    onClick={() => chain()?.toggleBold().run()}
                />
                <ToolButton
                    icon={Italic}
                    label="Miring"
                    active={state?.italic}
                    onClick={() => chain()?.toggleItalic().run()}
                />
                <ToolButton
                    icon={Underline}
                    label="Garis bawah"
                    active={state?.underline}
                    onClick={() => chain()?.toggleUnderline().run()}
                />
                <ToolButton
                    icon={Strikethrough}
                    label="Coret"
                    active={state?.strike}
                    onClick={() => chain()?.toggleStrike().run()}
                />
                <Divider />
                <ToolButton
                    icon={List}
                    label="Daftar berpoin"
                    active={state?.bullet}
                    onClick={() => chain()?.toggleBulletList().run()}
                />
                <ToolButton
                    icon={ListOrdered}
                    label="Daftar bernomor"
                    active={state?.ordered}
                    onClick={() => chain()?.toggleOrderedList().run()}
                />
                <ToolButton
                    icon={Quote}
                    label="Kutipan"
                    active={state?.quote}
                    onClick={() => chain()?.toggleBlockquote().run()}
                />
                <ToolButton
                    icon={Minus}
                    label="Garis pemisah"
                    onClick={() => chain()?.setHorizontalRule().run()}
                />
                <Divider />
                <ToolButton
                    icon={Link2}
                    label="Tautan"
                    active={state?.link}
                    onClick={setLink}
                />
                {state?.link && (
                    <ToolButton
                        icon={Unlink}
                        label="Hapus tautan"
                        onClick={() =>
                            chain()?.extendMarkRange('link').unsetLink().run()
                        }
                    />
                )}
                <ToolButton
                    icon={ImagePlus}
                    label="Sisipkan gambar"
                    disabled={uploading}
                    onClick={() => fileInput.current?.click()}
                />
                {uploading && <Spinner className="ml-1 size-4" />}
                <span className="ml-auto flex">
                    <ToolButton
                        icon={Undo2}
                        label="Urungkan"
                        disabled={!state?.canUndo}
                        onClick={() => chain()?.undo().run()}
                    />
                    <ToolButton
                        icon={Redo2}
                        label="Ulangi"
                        disabled={!state?.canRedo}
                        onClick={() => chain()?.redo().run()}
                    />
                </span>
                <input
                    ref={fileInput}
                    type="file"
                    accept={IMAGE_TYPES.join(',')}
                    className="hidden"
                    onChange={(e) => {
                        const file = e.target.files?.[0];
                        e.target.value = '';

                        if (file) {
                            void uploadImage(file);
                        }
                    }}
                />
            </div>
            <EditorContent editor={editor} />
        </div>
    );
}
