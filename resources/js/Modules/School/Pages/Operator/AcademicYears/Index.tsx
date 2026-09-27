import { Head, router } from '@inertiajs/react';
import {
    CalendarRange,
    CheckCircle2,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';
import { PeriodFormDialog } from '@/Modules/School/Components/PeriodFormDialog';
import type { AcademicYearRow, SemesterRow } from '@/Modules/School/types';
import type { Paginated } from '@/types';

type Props = {
    academicYears: Paginated<AcademicYearRow>;
};

type FormState =
    | { kind: 'create' }
    | { kind: 'year'; year: AcademicYearRow }
    | { kind: 'semester'; year: AcademicYearRow; semester: SemesterRow }
    | null;

type ConfirmState =
    | { kind: 'activate-year'; year: AcademicYearRow }
    | {
          kind: 'activate-semester';
          year: AcademicYearRow;
          semester: SemesterRow;
      }
    | { kind: 'delete'; year: AcademicYearRow }
    | null;

const BASE = '/operator/master-data';

function defaultNewYear() {
    const now = new Date();
    const start =
        now.getMonth() >= 6 ? now.getFullYear() + 1 : now.getFullYear();

    return {
        name: `${start}/${start + 1}`,
        start_date: `${start}-07-01`,
        end_date: `${start + 1}-06-30`,
    };
}

function range(start: string, end: string) {
    return `${formatDate(start)} – ${formatDate(end)}`;
}

export default function AcademicYearsIndex({ academicYears }: Props) {
    const [formState, setFormState] = useState<FormState>(null);
    const [confirm, setConfirm] = useState<ConfirmState>(null);
    const [processing, setProcessing] = useState(false);

    const runConfirm = () => {
        if (!confirm) {
            return;
        }

        const options = {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onError: (errors: Record<string, string>) =>
                toastFirstError(errors),
            onFinish: () => {
                setProcessing(false);
                setConfirm(null);
            },
        };

        if (confirm.kind === 'activate-year') {
            router.post(
                `${BASE}/tahun-ajaran/${confirm.year.public_id}/aktifkan`,
                {},
                options,
            );
        } else if (confirm.kind === 'activate-semester') {
            router.post(
                `${BASE}/semester/${confirm.semester.public_id}/aktifkan`,
                {},
                options,
            );
        } else {
            router.delete(
                `${BASE}/tahun-ajaran/${confirm.year.public_id}`,
                options,
            );
        }
    };

    const columns: DataTableColumn<AcademicYearRow>[] = [
        {
            id: 'name',
            header: 'Tahun ajaran',
            cell: ({ row }) => (
                <div className="min-w-40">
                    <div className="flex items-center gap-2">
                        <span className="font-semibold text-slate-900 tabular-nums">
                            {row.original.name}
                        </span>
                        {row.original.is_active && (
                            <StatusBadge variant="success" dot>
                                Aktif
                            </StatusBadge>
                        )}
                    </div>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {range(row.original.start_date, row.original.end_date)}
                    </p>
                </div>
            ),
        },
        {
            id: 'semesters',
            header: 'Semester',
            cell: ({ row }) => (
                <div className="flex min-w-72 flex-col gap-2">
                    {row.original.semesters.map((semester) => (
                        <div
                            key={semester.public_id}
                            className="flex items-center justify-between gap-2 rounded-xl border border-slate-200 px-3 py-2"
                        >
                            <div>
                                <p className="flex items-center gap-2 text-sm font-medium text-slate-800">
                                    {semester.label}
                                    {semester.is_active && (
                                        <StatusBadge variant="brand" dot>
                                            Aktif
                                        </StatusBadge>
                                    )}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {range(
                                        semester.start_date,
                                        semester.end_date,
                                    )}
                                </p>
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-8"
                                    aria-label={`Ubah tanggal ${semester.label}`}
                                    onClick={() =>
                                        setFormState({
                                            kind: 'semester',
                                            year: row.original,
                                            semester,
                                        })
                                    }
                                >
                                    <Pencil className="size-4" />
                                </Button>
                                {!semester.is_active && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() =>
                                            setConfirm({
                                                kind: 'activate-semester',
                                                year: row.original,
                                                semester,
                                            })
                                        }
                                    >
                                        Aktifkan
                                    </Button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            ),
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            meta: { className: 'text-right' },
            cell: ({ row }) => (
                <div className="flex justify-end gap-1">
                    {!row.original.is_active && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setConfirm({
                                    kind: 'activate-year',
                                    year: row.original,
                                })
                            }
                        >
                            <CheckCircle2 />
                            Aktifkan
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label={`Ubah ${row.original.name}`}
                        onClick={() =>
                            setFormState({ kind: 'year', year: row.original })
                        }
                    >
                        <Pencil className="size-4" />
                    </Button>
                    {row.original.can_delete && (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            aria-label={`Hapus ${row.original.name}`}
                            onClick={() =>
                                setConfirm({
                                    kind: 'delete',
                                    year: row.original,
                                })
                            }
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const confirmCopy = (() => {
        if (!confirm) {
            return { title: '', description: '', label: '' };
        }

        if (confirm.kind === 'activate-year') {
            return {
                title: `Aktifkan tahun ajaran ${confirm.year.name}?`,
                description:
                    'Tahun ajaran lain akan dinonaktifkan. Data baru (presensi, nilai, rombel) akan tercatat pada tahun ajaran ini.',
                label: 'Ya, aktifkan',
            };
        }

        if (confirm.kind === 'activate-semester') {
            return {
                title: `Aktifkan ${confirm.semester.label} ${confirm.year.name}?`,
                description:
                    'Semester lain akan dinonaktifkan dan tahun ajarannya ikut menjadi aktif.',
                label: 'Ya, aktifkan',
            };
        }

        return {
            title: `Hapus tahun ajaran ${confirm.year.name}?`,
            description:
                'Tahun ajaran beserta kedua semesternya akan dihapus permanen.',
            label: 'Ya, hapus',
        };
    })();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: `${BASE}/tahun-ajaran` },
                { title: 'Tahun Ajaran', href: `${BASE}/tahun-ajaran` },
            ]}
        >
            <Head title="Tahun Ajaran" />

            <PageHeader
                title="Tahun Ajaran & Semester"
                description="Hanya satu tahun ajaran dan satu semester yang aktif pada satu waktu."
                actions={
                    <Button onClick={() => setFormState({ kind: 'create' })}>
                        <Plus />
                        Tambah tahun ajaran
                    </Button>
                }
            />

            <DataTable
                columns={columns}
                data={academicYears}
                getRowId={(row) => row.public_id}
                empty={{
                    title: 'Belum ada tahun ajaran',
                    description:
                        'Tambahkan tahun ajaran pertama, lalu aktifkan agar modul lain dapat dipakai.',
                    icon: CalendarRange,
                    action: (
                        <Button
                            size="sm"
                            onClick={() => setFormState({ kind: 'create' })}
                        >
                            <Plus />
                            Tambah tahun ajaran
                        </Button>
                    ),
                }}
            />

            <PeriodFormDialog
                open={formState !== null}
                onOpenChange={(open) => !open && setFormState(null)}
                withName={formState?.kind !== 'semester'}
                title={
                    formState?.kind === 'create'
                        ? 'Tambah tahun ajaran'
                        : formState?.kind === 'year'
                          ? `Ubah ${formState.year.name}`
                          : formState?.kind === 'semester'
                            ? `Ubah ${formState.semester.label} ${formState.year.name}`
                            : ''
                }
                description={
                    formState?.kind === 'create'
                        ? 'Semester Ganjil dan Genap dibuat otomatis dan tanggalnya bisa diubah.'
                        : undefined
                }
                action={
                    formState?.kind === 'year'
                        ? {
                              url: `${BASE}/tahun-ajaran/${formState.year.public_id}`,
                              method: 'put',
                          }
                        : formState?.kind === 'semester'
                          ? {
                                url: `${BASE}/semester/${formState.semester.public_id}`,
                                method: 'put',
                            }
                          : { url: `${BASE}/tahun-ajaran`, method: 'post' }
                }
                initial={
                    formState?.kind === 'year'
                        ? formState.year
                        : formState?.kind === 'semester'
                          ? formState.semester
                          : defaultNewYear()
                }
            />

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(open) => !open && setConfirm(null)}
                variant={confirm?.kind === 'delete' ? 'danger' : 'default'}
                title={confirmCopy.title}
                description={confirmCopy.description}
                confirmLabel={confirmCopy.label}
                processing={processing}
                onConfirm={runConfirm}
            />
        </AppLayout>
    );
}
