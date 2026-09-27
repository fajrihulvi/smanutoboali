import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option, SubjectRow } from '@/Modules/Academic/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = tambah mapel baru. */
    subject: SubjectRow | null;
    groups: Option[];
};

export function SubjectFormDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <SubjectForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function SubjectForm({
    subject,
    groups,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const form = useForm({
        code: subject?.code ?? '',
        name: subject?.name ?? '',
        group: subject?.group.value ?? groups[0]?.value ?? '',
        is_active: subject?.is_active ?? true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: onClose };

        if (subject) {
            form.put(
                `/operator/master-data/mapel/${subject.public_id}`,
                options,
            );
        } else {
            form.post('/operator/master-data/mapel', options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>
                    {subject ? `Ubah ${subject.name}` : 'Tambah mata pelajaran'}
                </DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="subject-code">Kode</Label>
                    <Input
                        id="subject-code"
                        value={form.data.code}
                        onChange={(event) =>
                            form.setData(
                                'code',
                                event.target.value.toUpperCase(),
                            )
                        }
                        placeholder="MTK"
                        aria-invalid={!!form.errors.code}
                    />
                    <InputError message={form.errors.code} />
                </div>
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="subject-name">Nama mata pelajaran</Label>
                    <Input
                        id="subject-name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        placeholder="Matematika"
                        aria-invalid={!!form.errors.name}
                    />
                    <InputError message={form.errors.name} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="subject-group">Kelompok</Label>
                <NativeSelect
                    id="subject-group"
                    value={form.data.group}
                    onChange={(event) =>
                        form.setData('group', event.target.value)
                    }
                    aria-invalid={!!form.errors.group}
                >
                    {groups.map((group) => (
                        <option key={group.value} value={group.value}>
                            {group.label}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.group} />
            </div>

            <label className="flex items-start gap-2 text-sm text-slate-700">
                <Checkbox
                    className="mt-0.5"
                    checked={form.data.is_active}
                    onCheckedChange={(checked) =>
                        form.setData('is_active', checked === true)
                    }
                />
                <span>
                    Aktif
                    <span className="block text-xs text-slate-500">
                        Mapel nonaktif tidak muncul di matriks penugasan
                        mengajar.
                    </span>
                </span>
            </label>

            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Simpan
                </Button>
            </DialogFooter>
        </form>
    );
}
