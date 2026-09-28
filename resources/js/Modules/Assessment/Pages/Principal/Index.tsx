import { Head, Link, router } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import { BarChart3, GraduationCap, TriangleAlert } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';

type Summary = {
    students: number;
    graded: number;
    average: number | null;
    below_kkm: number;
    distribution: Record<'A' | 'B' | 'C' | 'D', number>;
};

type Props = {
    semester: string | null;
    semesters: Option[];
    classrooms: Option[];
    classroom: string | null;
    rows: {
        public_id: string;
        subject: string;
        teacher: string;
        summary: Summary;
    }[];
    overall: Summary;
    kkm: number;
};

const BASE = '/kepala-sekolah/nilai';
const BAR: Record<'A' | 'B' | 'C' | 'D', string> = {
    A: 'bg-emerald-500',
    B: 'bg-sky-500',
    C: 'bg-amber-500',
    D: 'bg-rose-500',
};

function Distribution({ summary }: { summary: Summary }) {
    if (summary.graded === 0) {
        return (
            <span className="text-xs text-slate-400">
                Belum ada nilai akhir
            </span>
        );
    }

    return (
        <div
            className="flex h-2.5 w-40 overflow-hidden rounded-full bg-slate-100"
            title="Distribusi predikat A/B/C/D"
        >
            {(['A', 'B', 'C', 'D'] as const).map((p) =>
                summary.distribution[p] > 0 ? (
                    <div
                        key={p}
                        className={cn('h-full', BAR[p])}
                        style={{
                            width: `${(summary.distribution[p] / summary.graded) * 100}%`,
                        }}
                    />
                ) : null,
            )}
        </div>
    );
}

export default function PrincipalGrades({
    semester,
    semesters,
    classrooms,
    classroom,
    rows,
    overall,
    kkm,
}: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Rekap Nilai', href: BASE }]}>
            <Head title="Rekap Nilai" />

            <PageHeader
                title="Rekap Nilai"
                description={`Nilai akhir per mata pelajaran. KKM ${kkm}.`}
            />

            <div className="mb-4 flex flex-wrap gap-2">
                <SemesterSelect
                    semesters={semesters}
                    value={semester}
                    query={{ rombel: classroom ?? undefined }}
                />
                <NativeSelect
                    aria-label="Rombel"
                    className="w-40"
                    value={classroom ?? ''}
                    onChange={(event) =>
                        router.get(BASE, {
                            semester: semester ?? undefined,
                            rombel: event.target.value,
                        })
                    }
                >
                    {classrooms.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </NativeSelect>
            </div>

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard
                    label="Rata-rata nilai akhir"
                    value={
                        overall.average === null
                            ? '—'
                            : overall.average.toLocaleString('id-ID')
                    }
                    icon={BarChart3}
                />
                <StatCard
                    label="Nilai akhir terhitung"
                    value={`${overall.graded} / ${overall.students}`}
                    icon={GraduationCap}
                />
                <StatCard
                    label="Di bawah KKM"
                    value={overall.below_kkm}
                    icon={TriangleAlert}
                />
            </div>

            {rows.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={BookOpen}
                        title="Belum ada mapel di rombel ini"
                    />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="relative overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                <tr>
                                    <th className="px-4 py-3">
                                        Mata pelajaran
                                    </th>
                                    <th className="px-3 py-3 text-right">
                                        Rata-rata
                                    </th>
                                    <th className="px-3 py-3 text-right">
                                        Terhitung
                                    </th>
                                    <th className="px-3 py-3 text-right">
                                        &lt; KKM
                                    </th>
                                    <th className="px-4 py-3">
                                        Predikat A/B/C/D
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.map((row) => (
                                    <tr key={row.public_id}>
                                        <td className="px-4 py-3">
                                            <Link
                                                href={`${BASE}/${row.public_id}?semester=${semester ?? ''}`}
                                                className="font-medium text-slate-900 hover:text-brand-700"
                                            >
                                                {row.subject}
                                            </Link>
                                            <p className="text-xs text-slate-500">
                                                {row.teacher}
                                            </p>
                                        </td>
                                        <td className="px-3 py-3 text-right font-semibold tabular-nums">
                                            {row.summary.average === null
                                                ? '—'
                                                : row.summary.average.toLocaleString(
                                                      'id-ID',
                                                  )}
                                        </td>
                                        <td className="px-3 py-3 text-right tabular-nums">
                                            {row.summary.graded}/
                                            {row.summary.students}
                                        </td>
                                        <td
                                            className={cn(
                                                'px-3 py-3 text-right tabular-nums',
                                                row.summary.below_kkm > 0 &&
                                                    'text-rose-600',
                                            )}
                                        >
                                            {row.summary.below_kkm}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <Distribution
                                                    summary={row.summary}
                                                />
                                                {row.summary.graded > 0 && (
                                                    <span className="text-xs whitespace-nowrap text-slate-500 tabular-nums">
                                                        {
                                                            row.summary
                                                                .distribution.A
                                                        }
                                                        /
                                                        {
                                                            row.summary
                                                                .distribution.B
                                                        }
                                                        /
                                                        {
                                                            row.summary
                                                                .distribution.C
                                                        }
                                                        /
                                                        {
                                                            row.summary
                                                                .distribution.D
                                                        }
                                                    </span>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            )}
        </AppLayout>
    );
}
