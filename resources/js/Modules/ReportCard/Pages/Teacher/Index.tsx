import { Head, Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    ChevronRight,
    CircleDashed,
    FileText,
    UserRoundX,
} from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';
import type { Option } from '@/Modules/Academic/types';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';
import { StatusCounts } from '@/Modules/ReportCard/Components/StatusCounts';
import type { StatusCounts as Counts } from '@/Modules/ReportCard/types';

type Props = {
    hasTeacherProfile: boolean;
    semester: string | null;
    semesters: Option[];
    subjects: {
        assignment: string;
        subject: string;
        classroom: string;
        confirmed_at: string | null;
        readiness: {
            students: number;
            missing_final: number;
            missing_description: number;
            ready: boolean;
        };
    }[];
    homeroom: {
        public_id: string;
        name: string;
        counts: Counts;
        total: number;
        confirmed: number;
        subjects: number;
    }[];
};

const BASE = '/guru/raport';

export default function TeacherReportCards({
    hasTeacherProfile,
    semester,
    semesters,
    subjects,
    homeroom,
}: Props) {
    const q = `?semester=${semester ?? ''}`;
    const act = (method: 'post' | 'delete', assignment: string) =>
        router[method](
            `${BASE}/konfirmasi/${assignment}${q}`,
            {},
            { preserveScroll: true, onError: (e) => toastFirstError(e) },
        );

    return (
        <AppLayout breadcrumbs={[{ title: 'Raport', href: BASE }]}>
            <Head title="Raport" />

            <PageHeader
                title="Raport"
                description="Konfirmasi mapel yang Anda ampu, lalu wali kelas mereview raport rombelnya."
                actions={
                    semesters.length > 0 && (
                        <SemesterSelect
                            semesters={semesters}
                            value={semester}
                        />
                    )
                }
            />

            {!hasTeacherProfile ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Akun belum terhubung ke data guru"
                    />
                </div>
            ) : (
                <div className="space-y-8">
                    {homeroom.length > 0 && (
                        <section>
                            <h2 className="mb-3 text-base font-semibold text-slate-900">
                                Rombel perwalian
                            </h2>
                            <ul className="grid gap-3 sm:grid-cols-2">
                                {homeroom.map((classroom) => (
                                    <li key={classroom.public_id}>
                                        <Link
                                            href={`${BASE}/rombel/${classroom.public_id}${q}`}
                                            className="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-brand-300"
                                        >
                                            <div className="flex items-start justify-between">
                                                <p className="text-lg font-semibold text-slate-900">
                                                    {classroom.name}
                                                </p>
                                                <p className="text-sm text-slate-500 tabular-nums">
                                                    Mapel dikonfirmasi{' '}
                                                    {classroom.confirmed}/
                                                    {classroom.subjects}
                                                </p>
                                            </div>
                                            <div className="mt-3">
                                                <StatusCounts
                                                    counts={classroom.counts}
                                                />
                                            </div>
                                            <p className="mt-auto flex items-center gap-1 pt-4 text-sm font-medium text-brand-700">
                                                Kelola raport
                                                <ChevronRight className="size-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                                            </p>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}

                    <section>
                        <h2 className="mb-1 text-base font-semibold text-slate-900">
                            Konfirmasi mapel
                        </h2>
                        <p className="mb-3 text-sm text-slate-500">
                            Konfirmasi setelah semua siswa punya nilai akhir dan
                            deskripsi. Setelah semua mapel rombel dikonfirmasi,
                            raport otomatis diajukan ke wali kelas.
                        </p>
                        {subjects.length === 0 ? (
                            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <EmptyState
                                    icon={FileText}
                                    title="Tidak ada mapel yang diampu"
                                />
                            </div>
                        ) : (
                            <ul className="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                {subjects.map((row) => (
                                    <li
                                        key={row.assignment}
                                        className="flex flex-wrap items-center gap-3 px-5 py-3"
                                    >
                                        {row.confirmed_at ? (
                                            <CheckCircle2
                                                className="size-5 shrink-0 text-emerald-500"
                                                aria-label="Dikonfirmasi"
                                            />
                                        ) : (
                                            <CircleDashed
                                                className="size-5 shrink-0 text-slate-300"
                                                aria-label="Belum dikonfirmasi"
                                            />
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium text-slate-900">
                                                {row.subject} · {row.classroom}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                {row.confirmed_at
                                                    ? `Dikonfirmasi ${formatDateTime(row.confirmed_at)}`
                                                    : row.readiness.ready
                                                      ? 'Siap dikonfirmasi'
                                                      : `${row.readiness.missing_final} siswa belum punya nilai akhir, ${row.readiness.missing_description} belum punya deskripsi`}
                                            </p>
                                        </div>
                                        {row.confirmed_at ? (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    act(
                                                        'delete',
                                                        row.assignment,
                                                    )
                                                }
                                            >
                                                Batalkan
                                            </Button>
                                        ) : (
                                            <Button
                                                size="sm"
                                                disabled={!row.readiness.ready}
                                                onClick={() =>
                                                    act('post', row.assignment)
                                                }
                                            >
                                                Konfirmasi
                                            </Button>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            )}
        </AppLayout>
    );
}
