import { Head } from '@inertiajs/react';
import { Eye, History } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/lib/format';
import type { Paginated } from '@/types';

type AuditLogRow = {
    key: string;
    module: string | null;
    module_label: string;
    event: string | null;
    description: string;
    causer: { name: string; email: string } | null;
    subject_type: string | null;
    ip: string | null;
    user_agent: string | null;
    changes: Record<string, unknown>;
    created_at: string;
};

type Props = {
    logs: Paginated<AuditLogRow>;
    filters: { module?: string; user?: string; from?: string; to?: string };
    modules: { value: string; label: string }[];
    basePath: string;
};

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '—';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
}

function ChangesTable({ changes }: { changes: Record<string, unknown> }) {
    const old = (changes.old ?? {}) as Record<string, unknown>;
    const next = (changes.attributes ?? {}) as Record<string, unknown>;
    const fields = [...new Set([...Object.keys(old), ...Object.keys(next)])];
    const extra = Object.entries(changes).filter(
        ([key]) => key !== 'old' && key !== 'attributes',
    );

    if (fields.length === 0 && extra.length === 0) {
        return (
            <p className="text-sm text-slate-500">
                Tidak ada detail perubahan.
            </p>
        );
    }

    return (
        <div className="space-y-4">
            {fields.length > 0 && (
                <div className="overflow-x-auto rounded-xl border border-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th className="px-3 py-2 text-left font-semibold">
                                    Kolom
                                </th>
                                <th className="px-3 py-2 text-left font-semibold">
                                    Sebelum
                                </th>
                                <th className="px-3 py-2 text-left font-semibold">
                                    Sesudah
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {fields.map((field) => (
                                <tr key={field}>
                                    <td className="px-3 py-2 font-medium text-slate-700">
                                        {field}
                                    </td>
                                    <td className="px-3 py-2 text-rose-700">
                                        {formatValue(old[field])}
                                    </td>
                                    <td className="px-3 py-2 text-emerald-700">
                                        {formatValue(next[field])}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
            {extra.length > 0 && (
                <dl className="grid gap-2 text-sm">
                    {extra.map(([key, value]) => (
                        <div key={key} className="grid grid-cols-3 gap-2">
                            <dt className="font-medium text-slate-700">
                                {key}
                            </dt>
                            <dd className="col-span-2 break-words text-slate-600">
                                {formatValue(value)}
                            </dd>
                        </div>
                    ))}
                </dl>
            )}
        </div>
    );
}

export default function AuditLogIndex({
    logs,
    filters,
    modules,
    basePath,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            module: filters.module,
            user: filters.user,
            from: filters.from,
            to: filters.to,
        },
        { only: ['logs', 'filters'] },
    );
    const [detail, setDetail] = useState<AuditLogRow | null>(null);

    const columns: DataTableColumn<AuditLogRow>[] = [
        {
            id: 'created_at',
            header: 'Waktu',
            cell: ({ row }) => (
                <span className="text-sm whitespace-nowrap text-slate-600 tabular-nums">
                    {formatDateTime(row.original.created_at)}
                </span>
            ),
        },
        {
            id: 'causer',
            header: 'Pengguna',
            cell: ({ row }) =>
                row.original.causer ? (
                    <div className="min-w-36">
                        <p className="font-medium text-slate-900">
                            {row.original.causer.name}
                        </p>
                        <p className="text-xs text-slate-500">
                            {row.original.causer.email}
                        </p>
                    </div>
                ) : (
                    <span className="text-xs text-slate-400">Sistem</span>
                ),
        },
        {
            id: 'module',
            header: 'Modul',
            cell: ({ row }) => (
                <StatusBadge variant="brand">
                    {row.original.module_label}
                </StatusBadge>
            ),
        },
        {
            id: 'description',
            header: 'Aktivitas',
            cell: ({ row }) => (
                <div className="min-w-48">
                    <p className="text-slate-800">{row.original.description}</p>
                    {row.original.ip && (
                        <p className="text-xs text-slate-400">
                            IP {row.original.ip}
                        </p>
                    )}
                </div>
            ),
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Detail</span>,
            meta: { className: 'text-right' },
            cell: ({ row }) => (
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setDetail(row.original)}
                >
                    <Eye />
                    Detail
                </Button>
            ),
        },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'Audit Log', href: basePath }]}>
            <Head title="Audit Log" />

            <PageHeader
                title="Audit Log"
                description="Jejak aktivitas penting pengguna. Data hanya dapat dilihat, tidak dapat diubah."
            />

            <DataTable
                columns={columns}
                data={logs}
                getRowId={(row) => row.key}
                only={['logs', 'filters']}
                empty={{
                    title: isActive
                        ? 'Tidak ada aktivitas yang cocok'
                        : 'Belum ada aktivitas',
                    description: isActive
                        ? 'Coba ubah filter.'
                        : 'Aktivitas akan tercatat otomatis.',
                    icon: History,
                }}
                toolbar={
                    <FilterBar
                        search={values.user}
                        onSearchChange={(value) => set('user', value, true)}
                        searchPlaceholder="Cari nama/email pengguna…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <Select
                            value={values.module ?? 'all'}
                            onValueChange={(value) =>
                                set(
                                    'module',
                                    value === 'all' ? undefined : value,
                                )
                            }
                        >
                            <SelectTrigger
                                className="w-44"
                                aria-label="Filter modul"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua modul</SelectItem>
                                {modules.map((module) => (
                                    <SelectItem
                                        key={module.value}
                                        value={module.value}
                                    >
                                        {module.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Input
                            type="date"
                            value={values.from ?? ''}
                            onChange={(event) =>
                                set('from', event.target.value || undefined)
                            }
                            className="w-40"
                            aria-label="Dari tanggal"
                        />
                        <span className="text-sm text-slate-400">s.d.</span>
                        <Input
                            type="date"
                            value={values.to ?? ''}
                            onChange={(event) =>
                                set('to', event.target.value || undefined)
                            }
                            className="w-40"
                            aria-label="Sampai tanggal"
                        />
                    </FilterBar>
                }
            />

            <Dialog
                open={detail !== null}
                onOpenChange={(open) => !open && setDetail(null)}
            >
                <DialogContent className="sm:max-w-2xl">
                    {detail && (
                        <>
                            <DialogHeader>
                                <DialogTitle>{detail.description}</DialogTitle>
                                <DialogDescription>
                                    {formatDateTime(detail.created_at)} ·{' '}
                                    {detail.module_label}
                                    {detail.causer &&
                                        ` · ${detail.causer.name}`}
                                </DialogDescription>
                            </DialogHeader>
                            <ChangesTable changes={detail.changes} />
                            {(detail.ip || detail.user_agent) && (
                                <p className="text-xs break-all text-slate-400">
                                    {detail.ip} · {detail.user_agent}
                                </p>
                            )}
                        </>
                    )}
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
