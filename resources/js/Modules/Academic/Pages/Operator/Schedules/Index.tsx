import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, CalendarX2, Plus, Shapes } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import { ScheduleFormDialog } from '@/Modules/Academic/Components/ScheduleFormDialog';
import { WeeklySchedule } from '@/Modules/Academic/Components/WeeklySchedule';
import { YearSelect } from '@/Modules/Academic/Components/YearSelect';
import { formatDuration, totalMinutes } from '@/Modules/Academic/lib/schedule';
import type {
    DayOption,
    Option,
    ScheduleEntry,
    YearOption,
} from '@/Modules/Academic/types';

type Props = {
    year: YearOption | null;
    years: YearOption[];
    classrooms: Option[];
    classroom: { public_id: string; name: string } | null;
    schedules: ScheduleEntry[];
    assignments: Option[];
    days: DayOption[];
};

const URL = '/operator/akademik/jadwal';

export default function SchedulesIndex({
    year,
    years,
    classrooms,
    classroom,
    schedules,
    assignments,
    days,
}: Props) {
    const [dialog, setDialog] = useState<{
        open: boolean;
        schedule: ScheduleEntry | null;
        day: number;
    }>({
        open: false,
        schedule: null,
        day: 1,
    });
    const { confirmDelete, dialog: deleteDialog } = useConfirmDelete();
    const minutes = totalMinutes(schedules);

    const empty = !year ? (
        <EmptyState
            icon={CalendarX2}
            title="Belum ada tahun ajaran"
            description="Buat tahun ajaran terlebih dahulu."
        />
    ) : !classroom ? (
        <EmptyState
            icon={Shapes}
            title="Belum ada rombel"
            description={`Buat rombel TA ${year.label} sebelum menyusun jadwal.`}
            action={
                <Button size="sm" asChild>
                    <Link
                        href={`/operator/master-data/kelas?tahun=${year.value}`}
                    >
                        Kelola rombel
                    </Link>
                </Button>
            }
        />
    ) : assignments.length === 0 ? (
        <EmptyState
            icon={BookOpen}
            title="Belum ada penugasan mengajar"
            description={`Tentukan guru pengampu mapel di ${classroom.name} sebelum menyusun jadwal.`}
            action={
                <Button size="sm" asChild>
                    <Link
                        href={`/operator/akademik/penugasan?tahun=${year.value}`}
                    >
                        Atur penugasan
                    </Link>
                </Button>
            }
        />
    ) : null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Akademik', href: URL },
                { title: 'Jadwal Pelajaran', href: URL },
            ]}
        >
            <Head title="Jadwal Pelajaran" />

            <PageHeader
                title="Jadwal Pelajaran"
                description="Susun jadwal mingguan per rombel. Bentrok guru dan bentrok rombel ditolak otomatis."
                actions={
                    classroom &&
                    assignments.length > 0 && (
                        <Button
                            onClick={() =>
                                setDialog({
                                    open: true,
                                    schedule: null,
                                    day: 1,
                                })
                            }
                        >
                            <Plus />
                            Tambah jadwal
                        </Button>
                    )
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-2">
                {year && <YearSelect years={years} value={year.value} />}
                {classrooms.length > 0 && (
                    <NativeSelect
                        aria-label="Pilih rombel"
                        className="w-40"
                        value={classroom?.public_id ?? ''}
                        onChange={(event) =>
                            router.get(
                                URL,
                                {
                                    tahun: year?.value,
                                    rombel: event.target.value,
                                },
                                { preserveScroll: true },
                            )
                        }
                    >
                        {classrooms.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </NativeSelect>
                )}
                {classroom && (
                    <p className="text-sm text-slate-500 tabular-nums">
                        {schedules.length} slot · {formatDuration(minutes)} per
                        minggu
                    </p>
                )}
            </div>

            {empty ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {empty}
                </div>
            ) : (
                <WeeklySchedule
                    days={days}
                    schedules={schedules}
                    detail="teacher"
                    showEmptySaturday
                    onSelect={(entry) =>
                        setDialog({
                            open: true,
                            schedule: entry,
                            day: entry.day,
                        })
                    }
                    renderDayFooter={(day) => (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="w-full text-slate-500"
                            onClick={() =>
                                setDialog({
                                    open: true,
                                    schedule: null,
                                    day: day.value,
                                })
                            }
                        >
                            <Plus />
                            Tambah
                        </Button>
                    )}
                />
            )}

            {classroom && (
                <ScheduleFormDialog
                    open={dialog.open}
                    onOpenChange={(open) =>
                        setDialog((current) => ({ ...current, open }))
                    }
                    schedule={dialog.schedule}
                    defaultDay={dialog.day}
                    classroomName={classroom.name}
                    assignments={assignments}
                    days={days}
                    onDelete={(entry) =>
                        confirmDelete({
                            url: `${URL}/${entry.public_id}`,
                            title: `Hapus jadwal ${entry.subject.name}?`,
                            description: `${entry.day_label} ${entry.start_time}–${entry.end_time} di ${entry.classroom.name}.`,
                        })
                    }
                />
            )}
            {deleteDialog}
        </AppLayout>
    );
}
