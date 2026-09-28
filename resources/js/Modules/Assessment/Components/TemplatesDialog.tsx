import { useForm } from '@inertiajs/react';
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
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import type { PredicateValue } from '@/Modules/Assessment/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    templates: Record<PredicateValue, string>;
    url: string;
};

const PREDICATES: { value: PredicateValue; label: string }[] = [
    { value: 'A', label: 'A — Sangat baik' },
    { value: 'B', label: 'B — Baik' },
    { value: 'C', label: 'C — Cukup' },
    { value: 'D', label: 'D — Perlu bimbingan' },
];

export function TemplatesDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <TemplatesForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function TemplatesForm({
    templates,
    url,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const form = useForm({ templates });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(url, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>Template deskripsi capaian</DialogTitle>
                <DialogDescription>
                    Dipakai untuk mengisi deskripsi sesuai predikat nilai akhir.
                    Gunakan <code>{'{nama}'}</code> dan <code>{'{mapel}'}</code>{' '}
                    sebagai isian otomatis.
                </DialogDescription>
            </DialogHeader>

            {PREDICATES.map((predicate) => (
                <div key={predicate.value} className="grid gap-2">
                    <Label htmlFor={`template-${predicate.value}`}>
                        {predicate.label}
                    </Label>
                    <Textarea
                        id={`template-${predicate.value}`}
                        rows={2}
                        maxLength={500}
                        value={form.data.templates[predicate.value]}
                        onChange={(event) =>
                            form.setData('templates', {
                                ...form.data.templates,
                                [predicate.value]: event.target.value,
                            })
                        }
                    />
                    <InputError
                        message={
                            form.errors[
                                `templates.${predicate.value}` as keyof typeof form.errors
                            ]
                        }
                    />
                </div>
            ))}

            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Simpan template
                </Button>
            </DialogFooter>
        </form>
    );
}
