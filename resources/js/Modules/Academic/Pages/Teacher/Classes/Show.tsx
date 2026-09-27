import { Head } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import type { ClassStudent, ClassroomRow } from '@/Modules/Academic/types';

type Props = {
    classroom: ClassroomRow;
    students: ClassStudent[];
};

export default function TeacherClassShow({ classroom, students }: Props) {
    const male = students.filter((student) => student.gender === 'L').length;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Kelas Saya', href: '/guru/kelas' },
                {
                    title: classroom.name,
                    href: `/guru/kelas/${classroom.public_id}`,
                },
            ]}
        >
            <Head title={`Kelas ${classroom.name}`} />

            <PageHeader
                title={`Kelas ${classroom.name}`}
                description={`Wali kelas: ${classroom.homeroom_teacher?.name ?? 'belum ditentukan'} · ${students.length} siswa (${male} L, ${students.length - male} P)`}
            />

            <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                {students.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="Belum ada siswa"
                        description="Operator belum menambahkan siswa ke rombel ini."
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {students.map((student, index) => (
                            <li
                                key={student.public_id}
                                className="flex items-center gap-3 px-5 py-3"
                            >
                                <span className="w-6 text-right text-xs text-slate-400 tabular-nums">
                                    {index + 1}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium text-slate-900">
                                        {student.name}
                                    </p>
                                    <p className="text-xs text-slate-500 tabular-nums">
                                        NIS {student.nis}
                                    </p>
                                </div>
                                <span className="text-xs font-medium text-slate-500">
                                    {student.gender}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </AppLayout>
    );
}
