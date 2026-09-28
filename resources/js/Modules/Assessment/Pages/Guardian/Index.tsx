import { Head } from '@inertiajs/react';
import { Baby, BookOpen } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { StudentSwitcher } from '@/Components/shared/StudentSwitcher';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Option } from '@/Modules/Academic/types';
import { PredicateChip } from '@/Modules/Assessment/Components/GradeGrid';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';
import { formatScore } from '@/Modules/Assessment/lib/score';
import type { PredicateValue } from '@/Modules/Assessment/types';

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    child: { public_id: string; name: string } | null;
    semester: string | null;
    semesters: Option[];
    subjects: {
        subject: string;
        components: {
            public_id: string;
            name: string;
            weight: number;
            score: number | null;
            published_at: string | null;
        }[];
        final: number | null;
        predicate: PredicateValue | null;
    }[];
    kkm: number;
};

export default function GuardianGrades({
    childOptions,
    activeChild,
    child,
    semester,
    semesters,
    subjects,
    kkm,
}: Props) {
    return (
        <ParentLayout
            header={
                <StudentSwitcher items={childOptions} active={activeChild} />
            }
        >
            <Head title="Nilai" />

            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Nilai
                    </h1>
                    <p className="text-sm text-slate-500">
                        {child
                            ? `${child.name} · KKM ${kkm}`
                            : 'Nilai yang sudah dipublikasikan guru.'}
                    </p>
                </div>
                {semesters.length > 0 && (
                    <SemesterSelect
                        semesters={semesters}
                        value={semester}
                        query={{ anak: activeChild ?? undefined }}
                    />
                )}
            </div>

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : subjects.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={BookOpen}
                        title="Belum ada nilai"
                        description="Nilai tampil setelah guru mempublikasikannya."
                    />
                </div>
            ) : (
                <ul className="space-y-3">
                    {subjects.map((row) => (
                        <li
                            key={row.subject}
                            className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h2 className="font-semibold text-slate-900">
                                    {row.subject}
                                </h2>
                                {row.final !== null && row.predicate && (
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={cn(
                                                'text-lg font-semibold tabular-nums',
                                                row.final < kkm
                                                    ? 'text-rose-600'
                                                    : 'text-slate-900',
                                            )}
                                        >
                                            {row.final}
                                        </span>
                                        <PredicateChip value={row.predicate} />
                                    </div>
                                )}
                            </div>
                            <ul className="mt-3 divide-y divide-slate-100">
                                {row.components.map((component) => (
                                    <li
                                        key={component.public_id}
                                        className="flex items-center justify-between gap-3 py-2 text-sm"
                                    >
                                        <span className="min-w-0">
                                            <span className="block text-slate-700">
                                                {component.name}
                                            </span>
                                            {component.published_at && (
                                                <span className="block text-xs text-slate-400">
                                                    {formatDate(
                                                        component.published_at,
                                                    )}
                                                </span>
                                            )}
                                        </span>
                                        <span
                                            className={cn(
                                                'font-semibold tabular-nums',
                                                component.score !== null &&
                                                    component.score < kkm
                                                    ? 'text-rose-600'
                                                    : 'text-slate-900',
                                            )}
                                        >
                                            {component.score === null
                                                ? '—'
                                                : formatScore(component.score)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                            {row.final === null && (
                                <p className="mt-2 text-xs text-slate-500">
                                    Nilai akhir tampil setelah semua komponen
                                    dipublikasikan.
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </ParentLayout>
    );
}
