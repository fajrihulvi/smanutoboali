import { Sparkles } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import { HttpError, sendJson } from '@/lib/http';

export type DescriptionTarget = {
    student: { public_id: string; name: string };
    description: string | null;
    suggested: string | null;
};

type Props = {
    target: DescriptionTarget | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    url: string;
    readOnly: boolean;
    onSaved: (studentPublicId: string, description: string | null) => void;
};

export function DescriptionDialog({
    target,
    open,
    onOpenChange,
    ...props
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                {target && (
                    <DescriptionForm
                        key={target.student.public_id}
                        target={target}
                        {...props}
                        onClose={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function DescriptionForm({
    target,
    url,
    readOnly,
    onSaved,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange' | 'target'> & {
    target: DescriptionTarget;
    onClose: () => void;
}) {
    const [text, setText] = useState(target.description ?? '');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setError(null);

        try {
            await sendJson(url, 'PUT', {
                student: target.student.public_id,
                description: text,
            });
            onSaved(
                target.student.public_id,
                text.trim() === '' ? null : text.trim(),
            );
            onClose();
        } catch (e) {
            setError(
                e instanceof HttpError
                    ? e.message
                    : 'Gagal menyimpan deskripsi.',
            );
        } finally {
            setSaving(false);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>Deskripsi capaian</DialogTitle>
                <DialogDescription>{target.student.name}</DialogDescription>
            </DialogHeader>

            {readOnly ? (
                <p className="text-sm whitespace-pre-line text-slate-700">
                    {target.description ?? 'Belum ada deskripsi.'}
                </p>
            ) : (
                <>
                    <Textarea
                        rows={5}
                        maxLength={1000}
                        value={text}
                        onChange={(event) => setText(event.target.value)}
                        aria-label="Deskripsi capaian"
                        placeholder="Tuliskan capaian kompetensi siswa…"
                    />
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        {target.suggested ? (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setText(target.suggested ?? '')}
                            >
                                <Sparkles />
                                Pakai template
                            </Button>
                        ) : (
                            <p className="text-xs text-slate-500">
                                Template tersedia setelah nilai akhir lengkap.
                            </p>
                        )}
                        <p className="text-xs text-slate-400 tabular-nums">
                            {text.length}/1000
                        </p>
                    </div>
                    <InputError message={error ?? undefined} />
                </>
            )}

            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    {readOnly ? 'Tutup' : 'Batal'}
                </Button>
                {!readOnly && (
                    <Button type="submit" disabled={saving}>
                        {saving && <Spinner />}
                        Simpan
                    </Button>
                )}
            </DialogFooter>
        </form>
    );
}
