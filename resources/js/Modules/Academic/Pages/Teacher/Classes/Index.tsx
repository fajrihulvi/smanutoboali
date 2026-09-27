import { Head, Link } from '@inertiajs/react';
import { ChevronRight, School, UserRoundX, Users } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import AppLayout from '@/Layouts/AppLayout';
import type { ClassroomRow } from '@/Modules/Academic/types';

type Props = {
    hasTeacherProfile: boolean;
    classrooms: (ClassroomRow & { is_homeroom: boolean; subjects: string[] })[];
};

export default function TeacherClassesIndex({
    hasTeacherProfile,
    classrooms,
}: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Kelas Saya', href: '/guru/kelas' }]}>
            <Head title="Kelas Saya" />

            <PageHeader
                title="Kelas Saya"
                description="Rombel tahun ajaran aktif tempat Anda menjadi wali kelas atau mengajar."
            />

            {!hasTeacherProfile ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Akun belum terhubung ke data guru"
                        description="Hubungi operator sekolah untuk menautkan akun Anda ke data guru."
                    />
                </div>
            ) : classrooms.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={School}
                        title="Belum ada kelas"
                        description="Anda belum ditugaskan sebagai wali kelas atau pengampu mapel di tahun ajaran aktif."
                    />
                </div>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {classrooms.map((classroom) => (
                        <Link
                            key={classroom.public_id}
                            href={`/guru/kelas/${classroom.public_id}`}
                            className="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-brand-300 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-lg font-semibold text-slate-900">
                                        {classroom.name}
                                    </p>
                                    <p className="text-sm text-slate-500">
                                        Kelas {classroom.grade_label}
                                    </p>
                                </div>
                                {classroom.is_homeroom && (
                                    <StatusBadge variant="brand">
                                        Wali kelas
                                    </StatusBadge>
                                )}
                            </div>
                            <p className="mt-4 flex items-center gap-2 text-sm text-slate-600 tabular-nums">
                                <Users className="size-4 text-slate-400" />
                                {classroom.students_count ?? 0} siswa
                            </p>
                            {classroom.subjects.length > 0 && (
                                <div className="mt-3 flex flex-wrap gap-1">
                                    {classroom.subjects.map((subject) => (
                                        <StatusBadge
                                            key={subject}
                                            variant="neutral"
                                        >
                                            {subject}
                                        </StatusBadge>
                                    ))}
                                </div>
                            )}
                            <p className="mt-4 flex items-center gap-1 text-sm font-medium text-brand-700">
                                Lihat siswa
                                <ChevronRight className="size-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                            </p>
                        </Link>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
