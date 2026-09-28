import { Head, useForm } from '@inertiajs/react';
import { Plus, Tags } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { RowActions } from '@/Components/shared/RowActions';
import { StatusBadge } from '@/Components/shared/StatusBadge';
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
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency } from '@/lib/format';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { RupiahInput } from '@/Modules/Finance/Components/RupiahInput';
import type { FeeTypeRow } from '@/Modules/Finance/types';

type Props = { feeTypes: FeeTypeRow[]; frequencies: Option[] };

const BASE = '/operator/keuangan/jenis-biaya';

export default function FeeTypes({ feeTypes, frequencies }: Props) {
    const [editing, setEditing] = useState<{
        open: boolean;
        row: FeeTypeRow | null;
    }>({ open: false, row: null });
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: '/operator/keuangan/tagihan' },
                { title: 'Jenis Biaya', href: BASE },
            ]}
        >
            <Head title="Jenis biaya" />
            <PageHeader
                title="Jenis biaya"
                description="SPP, uang kegiatan, daftar ulang, dan biaya lain. Nominal default bisa diubah saat membuat tagihan."
                actions={
                    <Button
                        onClick={() => setEditing({ open: true, row: null })}
                    >
                        <Plus />
                        Tambah jenis biaya
                    </Button>
                }
            />

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {feeTypes.length === 0 ? (
                    <EmptyState
                        icon={Tags}
                        title="Belum ada jenis biaya"
                        description="Tambahkan SPP atau biaya lain terlebih dahulu."
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {feeTypes.map((row) => (
                            <li
                                key={row.public_id}
                                className="flex items-center gap-3 px-4 py-3 sm:px-5"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium text-slate-900">
                                        {row.name}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {row.frequency.label} ·{' '}
                                        {formatCurrency(row.default_amount)} ·{' '}
                                        {row.invoices_count} tagihan
                                        {row.description &&
                                            ` · ${row.description}`}
                                    </p>
                                </div>
                                <StatusBadge
                                    variant={
                                        row.is_active ? 'success' : 'neutral'
                                    }
                                    dot
                                >
                                    {row.is_active ? 'Aktif' : 'Nonaktif'}
                                </StatusBadge>
                                <RowActions
                                    label={row.name}
                                    onEdit={() =>
                                        setEditing({ open: true, row })
                                    }
                                    onDelete={() =>
                                        confirmDelete({
                                            url: `${BASE}/${row.public_id}`,
                                            title: `Hapus ${row.name}?`,
                                            description:
                                                'Jenis biaya yang sudah dipakai di tagihan tidak bisa dihapus; nonaktifkan saja.',
                                        })
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <Dialog
                open={editing.open}
                onOpenChange={(open) => setEditing((c) => ({ ...c, open }))}
            >
                <DialogContent className="sm:max-w-md">
                    <FeeTypeForm
                        key={editing.row?.public_id ?? 'new'}
                        row={editing.row}
                        frequencies={frequencies}
                        onClose={() => setEditing({ open: false, row: null })}
                    />
                </DialogContent>
            </Dialog>
            {dialog}
        </AppLayout>
    );
}

function FeeTypeForm({
    row,
    frequencies,
    onClose,
}: {
    row: FeeTypeRow | null;
    frequencies: Option[];
    onClose: () => void;
}) {
    const form = useForm({
        name: row?.name ?? '',
        default_amount: (row?.default_amount ?? '') as number | '',
        frequency: row?.frequency.value ?? 'BULANAN',
        is_active: row?.is_active ?? true,
        description: row?.description ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (row) {
            form.put(`${BASE}/${row.public_id}`, options);
        } else {
            form.post(BASE, options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>
                    {row ? `Ubah ${row.name}` : 'Tambah jenis biaya'}
                </DialogTitle>
            </DialogHeader>
            <div className="grid gap-2">
                <Label htmlFor="fee-name">Nama</Label>
                <Input
                    id="fee-name"
                    maxLength={100}
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    placeholder="SPP"
                    aria-invalid={!!form.errors.name}
                />
                <InputError message={form.errors.name} />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="fee-amount">Nominal default</Label>
                    <RupiahInput
                        id="fee-amount"
                        value={form.data.default_amount}
                        onChange={(v) => form.setData('default_amount', v)}
                        invalid={!!form.errors.default_amount}
                    />
                    <InputError message={form.errors.default_amount} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="fee-frequency">Periode tagihan</Label>
                    <NativeSelect
                        id="fee-frequency"
                        value={form.data.frequency}
                        onChange={(e) =>
                            form.setData(
                                'frequency',
                                e.target.value as typeof form.data.frequency,
                            )
                        }
                    >
                        {frequencies.map((f) => (
                            <option key={f.value} value={f.value}>
                                {f.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.frequency} />
                </div>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="fee-description">Keterangan (opsional)</Label>
                <Input
                    id="fee-description"
                    maxLength={255}
                    value={form.data.description}
                    onChange={(e) =>
                        form.setData('description', e.target.value)
                    }
                />
            </div>
            <label className="flex items-center gap-2 text-sm">
                <Checkbox
                    checked={form.data.is_active}
                    onCheckedChange={(c) =>
                        form.setData('is_active', c === true)
                    }
                />
                Aktif (bisa dipilih saat membuat tagihan)
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
