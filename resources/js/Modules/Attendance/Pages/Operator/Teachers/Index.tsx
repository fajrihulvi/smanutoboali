import { Head, Link, router } from '@inertiajs/react';
import { BarChart3, Pencil, Users } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import type { Option } from '@/Modules/Academic/types';
import { TeacherAttendanceDialog } from '@/Modules/Attendance/Components/TeacherAttendanceDialog';
import { TEACHER_STATUS_VARIANT } from '@/Modules/Attendance/lib/teacher';
import type { TeacherRecord } from '@/Modules/Attendance/lib/teacher';

type Row = {
    teacher: { public_id: string; name: string; nip: string | null };
    record: TeacherRecord | null;
};

type Props = {
    date: string;
    rows: Row[];
    statuses: Option[];
};

const BASE = '/operator/presensi-guru';

export default function OperatorTeacherAttendance({
    date,
    rows,
    statuses,
}: Props) {
    const [editing, setEditing] = useState<Row | null>(null);
    const [open, setOpen] = useState(false);
    const today = new Date().toLocaleDateString('en-CA');
    const recorded = rows.filter((row) => row.record).length;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Presensi', href: '/operator/presensi' },
                { title: 'Presensi Guru', href: BASE },
            ]}
        >
            <Head title="Presensi Guru" />

            <PageHeader
                title="Presensi Guru"
                description={`${formatDate(date, 'EEEE, d MMMM yyyy')} · ${recorded} dari ${rows.length} guru tercatat.`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={`${BASE}/rekap`}>
                            <BarChart3 />
                            Rekap periode
                        </Link>
                    </Button>
                }
            />

            <div className="mb-4 grid max-w-44 gap-1">
                <label
                    htmlFor="teacher-date"
                    className="text-xs font-medium text-slate-500"
                >
                    Tanggal
                </label>
                <Input
                    id="teacher-date"
                    type="date"
                    max={today}
                    value={date}
                    onChange={(event) =>
                        event.target.value &&
                        router.get(
                            BASE,
                            { tanggal: event.target.value },
                            { preserveScroll: true },
                        )
                    }
                />
            </div>

            {rows.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Users} title="Belum ada guru aktif" />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <ul className="divide-y divide-slate-100">
                        {rows.map((row) => (
                            <li
                                key={row.teacher.public_id}
                                className="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 sm:px-5"
                            >
                                <div className="min-w-0 flex-1 basis-48">
                                    <p className="truncate font-medium text-slate-900">
                                        {row.teacher.name}
                                    </p>
                                    <p className="text-xs text-slate-500 tabular-nums">
                                        {row.teacher.nip
                                            ? `NIP ${row.teacher.nip}`
                                            : 'NIP belum diisi'}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    {row.record ? (
                                        <StatusBadge
                                            variant={
                                                TEACHER_STATUS_VARIANT[
                                                    row.record.status.value
                                                ]
                                            }
                                            dot
                                        >
                                            {row.record.status.label}
                                        </StatusBadge>
                                    ) : (
                                        <StatusBadge variant="neutral">
                                            Belum ada catatan
                                        </StatusBadge>
                                    )}
                                    {row.record?.source === 'OPERATOR' && (
                                        <span className="text-xs text-slate-400">
                                            dikoreksi
                                        </span>
                                    )}
                                </div>
                                {row.record?.check_in && (
                                    <p className="w-28 text-sm text-slate-600 tabular-nums">
                                        {row.record.check_in} –{' '}
                                        {row.record.check_out ?? '—'}
                                    </p>
                                )}
                                <p className="min-w-0 flex-1 basis-40 truncate text-sm text-slate-500">
                                    {row.record?.note}
                                </p>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => {
                                        setEditing(row);
                                        setOpen(true);
                                    }}
                                    aria-label={`Ubah presensi ${row.teacher.name}`}
                                >
                                    <Pencil />
                                    {row.record ? 'Koreksi' : 'Input'}
                                </Button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <TeacherAttendanceDialog
                open={open}
                onOpenChange={setOpen}
                teacher={editing?.teacher ?? null}
                record={editing?.record ?? null}
                date={date}
                statuses={statuses}
            />
        </AppLayout>
    );
}
