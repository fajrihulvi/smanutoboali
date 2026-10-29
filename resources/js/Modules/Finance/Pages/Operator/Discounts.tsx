import { Head, useForm } from '@inertiajs/react';
import { BadgePercent, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { RowActions } from '@/Components/shared/RowActions';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { StudentPicker } from '@/Components/shared/StudentPicker';
import type { PickedStudent } from '@/Components/shared/StudentPicker';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
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
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { RupiahInput } from '@/Modules/Finance/Components/RupiahInput';
import type { DiscountRow } from '@/Modules/Finance/types';
import type { Paginated } from '@/types';

type Props = {
    discounts: Paginated<DiscountRow>;
    filters: { q?: string };
    feeTypes: Option[];
    kinds: Option[];
};

const BASE = '/operator/keuangan/potongan';

export default function Discounts({
    discounts,
    filters,
    feeTypes,
    kinds,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        { q: filters.q },
        { only: ['discounts', 'filters'] },
    );
    const loading = useInertiaLoading();
    const [editing, setEditing] = useState<{
        open: boolean;
        row: DiscountRow | null;
    }>({ open: false, row: null });
    const { confirmDelete, dialog } = useConfirmDelete();

    const columns: DataTableColumn<DiscountRow>[] = [
        {
            id: 'student',
            header: 'Siswa',
            cell: ({ row }) => (
                <div>
                    <p className="font-medium text-slate-900">
                        {row.original.student.name}
                    </p>
                    <p className="text-xs text-slate-500">
                        {row.original.student.nis}
                    </p>
                </div>
            ),
        },
        {
            id: 'fee',
            header: 'Berlaku untuk',
            cell: ({ row }) =>
                row.original.fee_type?.name ?? 'Semua jenis biaya',
        },
        {
            id: 'value',
            header: 'Potongan',
            meta: { className: 'tabular-nums font-medium' },
            cell: ({ row }) => row.original.label,
        },
        {
            id: 'reason',
            header: 'Alasan',
            cell: ({ row }) => (
                <span className="text-slate-600">{row.original.reason}</span>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <StatusBadge
                    variant={row.original.is_active ? 'success' : 'neutral'}
                    dot
                >
                    {row.original.is_active ? 'Aktif' : 'Nonaktif'}
                </StatusBadge>
            ),
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            meta: { className: 'text-right' },
            cell: ({ row }) => (
                <RowActions
                    label={`potongan ${row.original.student.name}`}
                    onEdit={() => setEditing({ open: true, row: row.original })}
                    onDelete={() =>
                        confirmDelete({
                            url: `${BASE}/${row.original.public_id}`,
                            title: `Hapus potongan ${row.original.student.name}?`,
                            description:
                                'Tagihan yang sudah dibuat tidak berubah.',
                        })
                    }
                />
            ),
        },
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Keuangan', href: '/operator/keuangan/tagihan' },
                { title: 'Potongan', href: BASE },
            ]}
        >
            <Head title="Potongan & beasiswa" />
            <PageHeader
                title="Potongan & beasiswa"
                description="Aturan potongan per siswa, diterapkan otomatis saat tagihan baru dibuat. Tagihan yang sudah ada tidak berubah."
                actions={
                    <Button
                        onClick={() => setEditing({ open: true, row: null })}
                    >
                        <Plus />
                        Tambah potongan
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                data={discounts}
                getRowId={(row) => row.public_id}
                only={['discounts', 'filters']}
                loading={loading}
                empty={
                    isActive
                        ? {
                              title: 'Tidak ditemukan',
                              description: 'Ubah kata kunci.',
                          }
                        : {
                              title: 'Belum ada potongan',
                              description:
                                  'Tambahkan beasiswa atau keringanan untuk siswa.',
                              icon: BadgePercent,
                          }
                }
                toolbar={
                    <FilterBar
                        search={values.q}
                        onSearchChange={(v) => set('q', v, true)}
                        searchPlaceholder="Cari nama/NIS siswa…"
                        isActive={isActive}
                        onReset={reset}
                    />
                }
            />
            <Dialog
                open={editing.open}
                onOpenChange={(open) => setEditing((c) => ({ ...c, open }))}
            >
                <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
                    <DiscountForm
                        key={editing.row?.public_id ?? 'new'}
                        row={editing.row}
                        feeTypes={feeTypes}
                        kinds={kinds}
                        onClose={() => setEditing({ open: false, row: null })}
                    />
                </DialogContent>
            </Dialog>
            {dialog}
        </AppLayout>
    );
}

function DiscountForm({
    row,
    feeTypes,
    kinds,
    onClose,
}: {
    row: DiscountRow | null;
    feeTypes: Option[];
    kinds: Option[];
    onClose: () => void;
}) {
    const form = useForm({
        picked: [] as PickedStudent[],
        fee_type: row?.fee_type?.public_id ?? '',
        kind: row?.kind.value ?? 'PERSEN',
        value: (row?.value ?? '') as number | '',
        reason: row?.reason ?? '',
        is_active: row?.is_active ?? true,
    });
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform(({ picked, ...data }) => ({
            ...data,
            students: picked.map((s) => s.public_id),
        }));
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
                    {row
                        ? `Ubah potongan ${row.student.name}`
                        : 'Tambah potongan'}
                </DialogTitle>
                <DialogDescription>
                    Berlaku untuk tagihan yang dibuat setelah ini.
                </DialogDescription>
            </DialogHeader>
            {!row && (
                <div className="grid gap-2">
                    <Label>Siswa (boleh beberapa)</Label>
                    <StudentPicker
                        value={form.data.picked}
                        onChange={(picked) => form.setData('picked', picked)}
                        searchUrl={`${BASE}/siswa`}
                        invalid={!!errors.students}
                    />
                    <InputError message={errors.students} />
                </div>
            )}
            <div className="grid gap-2">
                <Label htmlFor="discount-fee">Berlaku untuk</Label>
                <NativeSelect
                    id="discount-fee"
                    value={form.data.fee_type}
                    onChange={(e) => form.setData('fee_type', e.target.value)}
                >
                    <option value="">Semua jenis biaya</option>
                    {feeTypes.map((f) => (
                        <option key={f.value} value={f.value}>
                            {f.label}
                        </option>
                    ))}
                </NativeSelect>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="discount-kind">Jenis potongan</Label>
                    <NativeSelect
                        id="discount-kind"
                        value={form.data.kind}
                        onChange={(e) =>
                            form.setData(
                                'kind',
                                e.target.value as typeof form.data.kind,
                            )
                        }
                    >
                        {kinds.map((k) => (
                            <option key={k.value} value={k.value}>
                                {k.label}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="discount-value">
                        {form.data.kind === 'PERSEN'
                            ? 'Persen (1–100)'
                            : 'Nominal'}
                    </Label>
                    {form.data.kind === 'PERSEN' ? (
                        <Input
                            id="discount-value"
                            type="number"
                            min={1}
                            max={100}
                            value={form.data.value}
                            onChange={(e) =>
                                form.setData(
                                    'value',
                                    e.target.value === ''
                                        ? ''
                                        : Number(e.target.value),
                                )
                            }
                            aria-invalid={!!errors.value}
                        />
                    ) : (
                        <RupiahInput
                            id="discount-value"
                            value={form.data.value}
                            onChange={(v) => form.setData('value', v)}
                            invalid={!!errors.value}
                        />
                    )}
                    <InputError message={errors.value} />
                </div>
            </div>
            <div className="grid gap-2">
                <Label htmlFor="discount-reason">Alasan</Label>
                <Input
                    id="discount-reason"
                    maxLength={150}
                    value={form.data.reason}
                    onChange={(e) => form.setData('reason', e.target.value)}
                    placeholder="Beasiswa prestasi"
                    aria-invalid={!!errors.reason}
                />
                <InputError message={errors.reason} />
            </div>
            <label className="flex items-center gap-2 text-sm">
                <Checkbox
                    checked={form.data.is_active}
                    onCheckedChange={(c) =>
                        form.setData('is_active', c === true)
                    }
                />
                Aktif
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
