import { Head, Link, router } from '@inertiajs/react';
import {
    BarChart3,
    CalendarX2,
    CheckCircle2,
    CircleDashed,
    Clock,
    UserRoundX,
} from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { STATUS_ORDER, STATUS_SHORT } from '@/Modules/Attendance/lib/status';
import type { Counts, ScheduleInfo } from '@/Modules/Attendance/types';

type Item = {
    key: string;
    classroom: { public_id: string; name: string };
    schedule: ScheduleInfo | null;
    recorded: boolean;
    counts: Counts | null;
    url: string;
};

type Props = {
    base: string;
    mode: Option;
    date: string;
    isSunday: boolean;
    items: Item[];
    hasActiveYear: boolean;
    hasTeacherProfile?: boolean;
    recapClassrooms: Option[];
};

export default function AttendanceIndex({
    base,
    mode,
    date,
    isSunday,
    items,
    hasActiveYear,
    hasTeacherProfile = true,
    recapClassrooms,
}: Props) {
    const today = new Date().toLocaleDateString('en-CA');
    const done = items.filter((item) => item.recorded).length;

    const empty = !hasTeacherProfile ? (
        <EmptyState
            icon={UserRoundX}
            title="Akun belum terhubung ke data guru"
            description="Hubungi operator sekolah untuk menautkan akun Anda ke data guru."
        />
    ) : !hasActiveYear ? (
        <EmptyState icon={CalendarX2} title="Belum ada tahun ajaran aktif" />
    ) : isSunday ? (
        <EmptyState
            icon={CalendarX2}
            title="Hari Minggu"
            description="Tidak ada presensi di hari Minggu."
        />
    ) : items.length === 0 ? (
        <EmptyState
            icon={CalendarX2}
            title={
                mode.value === 'PER_MAPEL'
                    ? 'Tidak ada jadwal pada tanggal ini'
                    : 'Belum ada rombel'
            }
            description={
                mode.value === 'PER_MAPEL'
                    ? 'Mode presensi per mata pelajaran mengikuti jadwal pelajaran.'
                    : 'Anda belum menjadi wali kelas atau mengajar di rombel tahun ajaran aktif.'
            }
        />
    ) : null;

    return (
        <AppLayout breadcrumbs={[{ title: 'Presensi', href: base }]}>
            <Head title="Presensi" />

            <PageHeader
                title="Presensi Siswa"
                description={`Mode ${mode.label.toLowerCase()}. ${formatDate(date, 'EEEE, d MMMM yyyy')}.`}
            />

            <div className="mb-4 flex flex-wrap items-end gap-3">
                <div className="grid gap-1">
                    <label
                        htmlFor="attendance-date"
                        className="text-xs font-medium text-slate-500"
                    >
                        Tanggal
                    </label>
                    <Input
                        id="attendance-date"
                        type="date"
                        max={today}
                        value={date}
                        onChange={(event) =>
                            event.target.value &&
                            router.get(
                                base,
                                { tanggal: event.target.value },
                                { preserveScroll: true },
                            )
                        }
                        className="w-44"
                    />
                </div>
                {items.length > 0 && (
                    <StatusBadge
                        variant={done === items.length ? 'success' : 'warning'}
                    >
                        {done} dari {items.length} sudah diisi
                    </StatusBadge>
                )}
                {recapClassrooms.length > 0 && (
                    <div className="ml-auto flex items-end gap-2">
                        <NativeSelect
                            aria-label="Rekap rombel"
                            className="w-40"
                            defaultValue=""
                            onChange={(event) =>
                                event.target.value &&
                                router.get(
                                    `${base}/rombel/${event.target.value}/rekap`,
                                )
                            }
                        >
                            <option value="" disabled>
                                Rekap rombel…
                            </option>
                            {recapClassrooms.map((classroom) => (
                                <option
                                    key={classroom.value}
                                    value={classroom.value}
                                >
                                    {classroom.label}
                                </option>
                            ))}
                        </NativeSelect>
                        <BarChart3
                            className="mb-2.5 size-4 text-slate-400"
                            aria-hidden="true"
                        />
                    </div>
                )}
            </div>

            {empty ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {empty}
                </div>
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {items.map((item) => (
                        <li
                            key={item.key}
                            className={cn(
                                'flex flex-col rounded-2xl border bg-white p-4 shadow-sm',
                                item.recorded
                                    ? 'border-slate-200'
                                    : 'border-amber-200',
                            )}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <p className="text-lg font-semibold text-slate-900">
                                        {item.classroom.name}
                                    </p>
                                    {item.schedule && (
                                        <p className="flex items-center gap-1 text-sm text-slate-600">
                                            <Clock className="size-3.5 text-slate-400" />
                                            <span className="tabular-nums">
                                                {item.schedule.start_time}–
                                                {item.schedule.end_time}
                                            </span>
                                            ·{' '}
                                            <span className="truncate">
                                                {item.schedule.subject}
                                            </span>
                                        </p>
                                    )}
                                </div>
                                {item.recorded ? (
                                    <CheckCircle2
                                        className="size-5 shrink-0 text-emerald-500"
                                        aria-label="Sudah diisi"
                                    />
                                ) : (
                                    <CircleDashed
                                        className="size-5 shrink-0 text-amber-500"
                                        aria-label="Belum diisi"
                                    />
                                )}
                            </div>

                            {item.counts ? (
                                <p className="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-600 tabular-nums">
                                    {STATUS_ORDER.map((status) => (
                                        <span key={status}>
                                            {STATUS_SHORT[status]}{' '}
                                            <strong>
                                                {item.counts?.[status]}
                                            </strong>
                                        </span>
                                    ))}
                                </p>
                            ) : (
                                <p className="mt-3 text-xs text-amber-700">
                                    Belum diisi
                                </p>
                            )}

                            <Button
                                className="mt-4"
                                variant={item.recorded ? 'outline' : 'default'}
                                asChild
                            >
                                <Link href={item.url}>
                                    {item.recorded
                                        ? 'Lihat / ubah'
                                        : 'Isi presensi'}
                                </Link>
                            </Button>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
