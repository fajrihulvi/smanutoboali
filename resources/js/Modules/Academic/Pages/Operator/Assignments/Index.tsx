import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, CalendarX2, Shapes } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import { toastFirstError } from '@/lib/toast-errors';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import { YearSelect } from '@/Modules/Academic/Components/YearSelect';
import type {
    Option,
    SubjectOption,
    YearOption,
} from '@/Modules/Academic/types';

type Props = {
    year: YearOption | null;
    years: YearOption[];
    classrooms: { public_id: string; name: string }[];
    subjects: SubjectOption[];
    teachers: Option[];
    /** Kunci "rombel:mapel" → public_id guru. */
    assignments: Record<string, string>;
};

const ASSIGNMENTS_URL = '/operator/akademik/penugasan';

export default function AssignmentsIndex({
    year,
    years,
    classrooms,
    subjects,
    teachers,
    assignments,
}: Props) {
    const [saving, setSaving] = useState<string | null>(null);

    const assign = (classroom: string, subject: string, teacher: string) => {
        const key = `${classroom}:${subject}`;

        router.put(
            ASSIGNMENTS_URL,
            { classroom, subject, teacher: teacher || null },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['assignments', 'flash'],
                onStart: () => setSaving(key),
                onError: (errors) => toastFirstError(errors),
                onFinish: () => setSaving(null),
            },
        );
    };

    const total = classrooms.length * subjects.length;
    const filled = Object.keys(assignments).length;

    const empty = !year ? (
        <EmptyState
            icon={CalendarX2}
            title="Belum ada tahun ajaran"
            description="Buat tahun ajaran terlebih dahulu."
        />
    ) : classrooms.length === 0 ? (
        <EmptyState
            icon={Shapes}
            title="Belum ada rombel"
            description={`Buat rombel TA ${year.label} sebelum mengatur penugasan.`}
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
    ) : subjects.length === 0 ? (
        <EmptyState
            icon={BookOpen}
            title="Belum ada mata pelajaran aktif"
            action={
                <Button size="sm" asChild>
                    <Link href="/operator/master-data/mapel">Kelola mapel</Link>
                </Button>
            }
        />
    ) : null;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Akademik', href: ASSIGNMENTS_URL },
                { title: 'Penugasan Mengajar', href: ASSIGNMENTS_URL },
            ]}
        >
            <Head title="Penugasan Mengajar" />

            <PageHeader
                title="Penugasan Mengajar"
                description="Tentukan guru pengampu setiap mata pelajaran di setiap rombel. Perubahan tersimpan otomatis."
            />

            <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="flex flex-wrap items-center gap-3 border-b border-slate-200 p-4">
                    {year && <YearSelect years={years} value={year.value} />}
                    {year && total > 0 && (
                        <StatusBadge
                            variant={filled === total ? 'success' : 'warning'}
                        >
                            {filled} dari {total} sel terisi
                        </StatusBadge>
                    )}
                </div>

                {empty ?? (
                    <div className="overflow-x-auto">
                        <table className="w-full border-separate border-spacing-0 text-sm">
                            <thead>
                                <tr>
                                    <th
                                        scope="col"
                                        className="sticky left-0 z-10 border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-xs font-semibold text-slate-600"
                                    >
                                        Mapel \ Rombel
                                    </th>
                                    {classrooms.map((classroom) => (
                                        <th
                                            key={classroom.public_id}
                                            scope="col"
                                            className="border-b border-slate-200 bg-slate-50 px-2 py-3 text-left text-xs font-semibold whitespace-nowrap text-slate-600"
                                        >
                                            <Link
                                                href={`/operator/master-data/kelas/${classroom.public_id}`}
                                                className="hover:text-brand-700"
                                            >
                                                {classroom.name}
                                            </Link>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {subjects.map((subject) => (
                                    <tr key={subject.public_id}>
                                        <th
                                            scope="row"
                                            className="sticky left-0 z-10 border-b border-slate-100 bg-white px-4 py-2 text-left font-medium whitespace-nowrap text-slate-900"
                                        >
                                            <span className="mr-2 font-mono text-xs text-slate-400">
                                                {subject.code}
                                            </span>
                                            {subject.name}
                                        </th>
                                        {classrooms.map((classroom) => {
                                            const key = `${classroom.public_id}:${subject.public_id}`;
                                            const value =
                                                assignments[key] ?? '';

                                            return (
                                                <td
                                                    key={key}
                                                    className="border-b border-slate-100 px-2 py-2"
                                                >
                                                    <NativeSelect
                                                        aria-label={`Guru ${subject.name} di ${classroom.name}`}
                                                        value={value}
                                                        disabled={
                                                            saving === key
                                                        }
                                                        onChange={(event) =>
                                                            assign(
                                                                classroom.public_id,
                                                                subject.public_id,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className={cn(
                                                            'h-9 w-44 text-xs',
                                                            !value &&
                                                                'border-dashed text-slate-400',
                                                            saving === key &&
                                                                'animate-pulse',
                                                        )}
                                                    >
                                                        <option value="">
                                                            — Kosong —
                                                        </option>
                                                        {teachers.map(
                                                            (teacher) => (
                                                                <option
                                                                    key={
                                                                        teacher.value
                                                                    }
                                                                    value={
                                                                        teacher.value
                                                                    }
                                                                >
                                                                    {
                                                                        teacher.label
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                    </NativeSelect>
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </AppLayout>
    );
}
