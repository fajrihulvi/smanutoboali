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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import type { GradeComponentRow } from '@/Modules/Assessment/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    component: GradeComponentRow | null;
    assignment: string;
    semester: string;
    types: Option[];
    remainingWeight: number;
};

const BASE = '/guru/akademik/nilai';

export function ComponentFormDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <ComponentForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function ComponentForm({
    component,
    assignment,
    semester,
    types,
    remainingWeight,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const form = useForm({
        semester,
        type: component?.type ?? types[0]?.value ?? 'TUGAS',
        name: component?.name ?? '',
        weight: String(
            component?.weight ?? Math.max(1, Math.min(remainingWeight, 100)),
        ),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (component) {
            form.put(`${BASE}/komponen/${component.public_id}`, options);
        } else {
            form.post(
                `${BASE}/${assignment}/komponen?semester=${semester}`,
                options,
            );
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>
                    {component ? 'Ubah komponen' : 'Tambah komponen nilai'}
                </DialogTitle>
                <DialogDescription>
                    Total bobot semua komponen harus 100% agar nilai akhir bisa
                    dihitung.
                </DialogDescription>
            </DialogHeader>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="component-type">Jenis</Label>
                    <NativeSelect
                        id="component-type"
                        value={form.data.type}
                        onChange={(event) =>
                            form.setData('type', event.target.value)
                        }
                    >
                        {types.map((type) => (
                            <option key={type.value} value={type.value}>
                                {type.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.type} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="component-weight">Bobot (%)</Label>
                    <Input
                        id="component-weight"
                        type="number"
                        min={1}
                        max={100}
                        value={form.data.weight}
                        onChange={(event) =>
                            form.setData('weight', event.target.value)
                        }
                        aria-invalid={!!form.errors.weight}
                    />
                    <InputError message={form.errors.weight} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="component-name">Nama</Label>
                <Input
                    id="component-name"
                    value={form.data.name}
                    maxLength={50}
                    placeholder="UH 1"
                    onChange={(event) =>
                        form.setData('name', event.target.value)
                    }
                    aria-invalid={!!form.errors.name}
                />
                <InputError message={form.errors.name} />
            </div>

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
