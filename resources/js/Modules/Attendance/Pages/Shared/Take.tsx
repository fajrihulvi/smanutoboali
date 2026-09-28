import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCheck,
    Lock,
    MessageSquarePlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate, formatDateTime } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
    STATUS_CELL,
    STATUS_ORDER,
    STATUS_SHORT,
} from '@/Modules/Attendance/lib/status';
import type {
    AttendanceStatusValue,
    ScheduleInfo,
    StatusOption,
} from '@/Modules/Attendance/types';

type RosterStudent = {
    public_id: string;
    nis: string;
    name: string;
    gender: string;
    status: AttendanceStatusValue | null;
    note: string | null;
};

type Props = {
    base: string;
    classroom: { public_id: string; name: string };
    date: string;
    schedule: ScheduleInfo | null;
    students: RosterStudent[];
    statuses: StatusOption[];
    session: {
        recorded_by: string | null;
        updated_by: string | null;
        updated_at: string | null;
    } | null;
    editable: boolean;
    editableUntil: string | null;
};

type Row = { student: string; status: AttendanceStatusValue; note: string };

export default function AttendanceTake({
    base,
    classroom,
    date,
    schedule,
    students,
    statuses,
    session,
    editable,
    editableUntil,
}: Props) {
    const form = useForm<{
        date: string;
        schedule: string | null;
        records: Row[];
    }>({
        date,
        schedule: schedule?.public_id ?? null,
        records: students.map((student) => ({
            student: student.public_id,
            status: student.status ?? 'HADIR',
            note: student.note ?? '',
        })),
    });
    const [openNotes, setOpenNotes] = useState<Set<string>>(
        () =>
            new Set(
                students
                    .filter((student) => student.note)
                    .map((student) => student.public_id),
            ),
    );

    const setRow = (index: number, patch: Partial<Row>) =>
        form.setData(
            'records',
            form.data.records.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );

    const markAllPresent = () =>
        form.setData(
            'records',
            form.data.records.map((row) => ({ ...row, status: 'HADIR' })),
        );

    const counts = STATUS_ORDER.map((status) => ({
        status,
        total: form.data.records.filter((row) => row.status === status).length,
    }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`${base}/rombel/${classroom.public_id}`, {
            preserveScroll: true,
        });
    };

    const label = (value: string) =>
        statuses.find((status) => status.value === value)?.label ?? value;
    const backUrl = `${base}?tanggal=${date}`;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Presensi', href: backUrl },
                { title: classroom.name, href: '#' },
            ]}
        >
            <Head title={`Presensi ${classroom.name}`} />

            <PageHeader
                title={`Presensi ${classroom.name}`}
                description={`${formatDate(date, 'EEEE, d MMMM yyyy')}${schedule ? ` · ${schedule.subject} ${schedule.start_time}–${schedule.end_time}` : ''}`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={backUrl}>
                            <ArrowLeft />
                            Kembali
                        </Link>
                    </Button>
                }
            />

            {!editable && (
                <div className="mb-4 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <Lock className="mt-0.5 size-4 shrink-0" />
                    <p>
                        Batas waktu mengisi/mengubah presensi ini sudah lewat
                        {editableUntil && ` (${formatDateTime(editableUntil)})`}
                        . Hubungi operator bila perlu koreksi.
                    </p>
                </div>
            )}

            {session && (
                <p className="mb-4 text-sm text-slate-500">
                    Diisi oleh {session.recorded_by ?? '—'}
                    {session.updated_by &&
                        `, terakhir diubah ${session.updated_by}`}
                    {session.updated_at &&
                        ` · ${formatDateTime(session.updated_at)}`}
                </p>
            )}

            <InputError
                className="mb-4"
                message={
                    form.errors.date ??
                    form.errors.schedule ??
                    form.errors.records
                }
            />

            {students.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Users}
                        title="Rombel belum punya siswa aktif"
                    />
                </div>
            ) : (
                <form onSubmit={submit}>
                    <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                            <p className="flex flex-wrap gap-2 text-xs tabular-nums">
                                {counts.map(({ status, total }) => (
                                    <span
                                        key={status}
                                        className={cn(
                                            'rounded-full px-2 py-0.5 font-medium',
                                            STATUS_CELL[status],
                                        )}
                                    >
                                        {label(status)} {total}
                                    </span>
                                ))}
                            </p>
                            {editable && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={markAllPresent}
                                >
                                    <CheckCheck />
                                    Tandai semua hadir
                                </Button>
                            )}
                        </div>

                        <ol className="divide-y divide-slate-100">
                            {students.map((student, index) => {
                                const row = form.data.records[index];
                                const noteOpen = openNotes.has(
                                    student.public_id,
                                );

                                return (
                                    <li
                                        key={student.public_id}
                                        className="px-4 py-3"
                                    >
                                        <div className="flex flex-col gap-2 md:flex-row md:items-center">
                                            <div className="flex min-w-0 flex-1 items-center gap-3">
                                                <span className="w-6 text-right text-xs text-slate-400 tabular-nums">
                                                    {index + 1}
                                                </span>
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-medium text-slate-900">
                                                        {student.name}
                                                    </p>
                                                    <p className="text-xs text-slate-500 tabular-nums">
                                                        NIS {student.nis}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-1.5 pl-9 md:pl-0">
                                                <div
                                                    role="radiogroup"
                                                    aria-label={`Status ${student.name}`}
                                                    className="flex gap-1"
                                                >
                                                    {STATUS_ORDER.map(
                                                        (status) => {
                                                            const active =
                                                                row.status ===
                                                                status;

                                                            return (
                                                                <button
                                                                    key={status}
                                                                    type="button"
                                                                    role="radio"
                                                                    aria-checked={
                                                                        active
                                                                    }
                                                                    aria-label={label(
                                                                        status,
                                                                    )}
                                                                    title={label(
                                                                        status,
                                                                    )}
                                                                    disabled={
                                                                        !editable
                                                                    }
                                                                    onClick={() =>
                                                                        setRow(
                                                                            index,
                                                                            {
                                                                                status,
                                                                            },
                                                                        )
                                                                    }
                                                                    className={cn(
                                                                        'h-9 min-w-9 rounded-lg border px-2 text-sm font-semibold transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none disabled:cursor-not-allowed lg:px-3',
                                                                        active
                                                                            ? cn(
                                                                                  'border-transparent',
                                                                                  STATUS_CELL[
                                                                                      status
                                                                                  ],
                                                                              )
                                                                            : 'border-slate-200 text-slate-400 hover:border-slate-300 hover:text-slate-600',
                                                                    )}
                                                                >
                                                                    <span className="lg:hidden">
                                                                        {
                                                                            STATUS_SHORT[
                                                                                status
                                                                            ]
                                                                        }
                                                                    </span>
                                                                    <span className="hidden lg:inline">
                                                                        {label(
                                                                            status,
                                                                        )}
                                                                    </span>
                                                                </button>
                                                            );
                                                        },
                                                    )}
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className={cn(
                                                        'size-9',
                                                        row.note &&
                                                            'text-brand-600',
                                                    )}
                                                    aria-label={`Catatan untuk ${student.name}`}
                                                    aria-expanded={noteOpen}
                                                    onClick={() =>
                                                        setOpenNotes(
                                                            (current) => {
                                                                const next =
                                                                    new Set(
                                                                        current,
                                                                    );

                                                                if (
                                                                    next.has(
                                                                        student.public_id,
                                                                    )
                                                                ) {
                                                                    next.delete(
                                                                        student.public_id,
                                                                    );
                                                                } else {
                                                                    next.add(
                                                                        student.public_id,
                                                                    );
                                                                }

                                                                return next;
                                                            },
                                                        )
                                                    }
                                                >
                                                    <MessageSquarePlus className="size-4" />
                                                </Button>
                                            </div>
                                        </div>
                                        {noteOpen && (
                                            <div className="mt-2 pl-9">
                                                <Input
                                                    value={row.note}
                                                    maxLength={255}
                                                    disabled={!editable}
                                                    placeholder="Catatan (mis. surat sakit, izin acara keluarga)"
                                                    onChange={(event) =>
                                                        setRow(index, {
                                                            note: event.target
                                                                .value,
                                                        })
                                                    }
                                                    aria-label={`Catatan ${student.name}`}
                                                />
                                                <InputError
                                                    message={
                                                        form.errors[
                                                            `records.${index}.note` as keyof typeof form.errors
                                                        ]
                                                    }
                                                />
                                            </div>
                                        )}
                                    </li>
                                );
                            })}
                        </ol>
                    </section>

                    {editable && (
                        <div className="sticky bottom-0 z-20 -mx-4 mt-4 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border sm:shadow-sm">
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm text-slate-500">
                                    {form.isDirty
                                        ? 'Ada perubahan yang belum disimpan.'
                                        : session
                                          ? 'Tersimpan.'
                                          : 'Belum disimpan.'}
                                </p>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    Simpan presensi
                                </Button>
                            </div>
                        </div>
                    )}
                </form>
            )}
        </AppLayout>
    );
}
