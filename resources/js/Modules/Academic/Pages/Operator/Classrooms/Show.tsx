import { Head, Link, router } from '@inertiajs/react';
import {
    BookOpen,
    Pencil,
    Trash2,
    UserMinus,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import { toastFirstError } from '@/lib/toast-errors';
import { ClassroomFormDialog } from '@/Modules/Academic/Components/ClassroomFormDialog';
import { EnrollStudentsDialog } from '@/Modules/Academic/Components/EnrollStudentsDialog';
import type {
    ClassStudent,
    ClassroomRow,
    GradeOption,
    Option,
    SubjectOption,
} from '@/Modules/Academic/types';

type Props = {
    classroom: ClassroomRow;
    students: ClassStudent[];
    assignments: {
        subject: SubjectOption;
        teacher: { public_id: string; name: string };
    }[];
    subjects: SubjectOption[];
    grades: GradeOption[];
    teachers: Option[];
};

const BASE = '/operator/master-data/kelas';

export default function ClassroomShow({
    classroom,
    students,
    assignments,
    subjects,
    grades,
    teachers,
}: Props) {
    const [editing, setEditing] = useState(false);
    const [enrolling, setEnrolling] = useState(false);
    const [removing, setRemoving] = useState<ClassStudent | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [processing, setProcessing] = useState(false);

    const unassigned = subjects.filter(
        (subject) =>
            !assignments.some(
                (assignment) =>
                    assignment.subject.public_id === subject.public_id,
            ),
    );

    const removeStudent = () => {
        if (!removing) {
            return;
        }

        router.delete(
            `${BASE}/${classroom.public_id}/siswa/${removing.public_id}`,
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onError: (errors) => toastFirstError(errors),
                onFinish: () => {
                    setProcessing(false);
                    setRemoving(null);
                },
            },
        );
    };

    const destroy = () => {
        router.delete(`${BASE}/${classroom.public_id}`, {
            onStart: () => setProcessing(true),
            onError: (errors) => toastFirstError(errors),
            onFinish: () => {
                setProcessing(false);
                setDeleting(false);
            },
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                {
                    title: 'Kelas',
                    href: `${BASE}?tahun=${classroom.academic_year?.public_id ?? ''}`,
                },
                {
                    title: classroom.name,
                    href: `${BASE}/${classroom.public_id}`,
                },
            ]}
        >
            <Head title={`Rombel ${classroom.name}`} />

            <PageHeader
                title={`Rombel ${classroom.name}`}
                description={`Kelas ${classroom.grade_label} · TA ${classroom.academic_year?.name ?? ''} · Wali kelas: ${classroom.homeroom_teacher?.name ?? 'belum ditentukan'}`}
                actions={
                    <>
                        <Button
                            variant="outline"
                            onClick={() => setEditing(true)}
                        >
                            <Pencil />
                            Ubah
                        </Button>
                        <Button
                            variant="outline"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() => setDeleting(true)}
                        >
                            <Trash2 />
                            Hapus
                        </Button>
                    </>
                }
            />

            <div className="grid gap-6 xl:grid-cols-5">
                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
                        <div>
                            <h2 className="text-base font-semibold text-slate-900">
                                Siswa
                            </h2>
                            <p className="text-sm text-slate-500 tabular-nums">
                                {students.length}
                                {classroom.capacity
                                    ? ` dari kapasitas ${classroom.capacity}`
                                    : ' siswa'}
                            </p>
                        </div>
                        <Button onClick={() => setEnrolling(true)}>
                            <UserPlus />
                            Tambah siswa
                        </Button>
                    </div>

                    {students.length === 0 ? (
                        <EmptyState
                            icon={Users}
                            title="Rombel masih kosong"
                            description="Tambahkan siswa ke rombel ini."
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
                                        <Link
                                            href={`/operator/master-data/siswa/${student.public_id}/ubah`}
                                            className="block truncate text-sm font-medium text-slate-900 hover:text-brand-700"
                                        >
                                            {student.name}
                                        </Link>
                                        <p className="text-xs text-slate-500 tabular-nums">
                                            NIS {student.nis} · {student.gender}
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                        aria-label={`Keluarkan ${student.name}`}
                                        onClick={() => setRemoving(student)}
                                    >
                                        <UserMinus className="size-4" />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                    <div className="flex items-center justify-between gap-3 border-b border-slate-200 p-5">
                        <h2 className="text-base font-semibold text-slate-900">
                            Penugasan mengajar
                        </h2>
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/operator/akademik/penugasan?tahun=${classroom.academic_year?.public_id ?? ''}`}
                            >
                                Atur
                            </Link>
                        </Button>
                    </div>
                    {assignments.length === 0 ? (
                        <EmptyState
                            icon={BookOpen}
                            title="Belum ada penugasan"
                            description="Atur guru pengampu di matriks penugasan."
                        />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {assignments.map((assignment) => (
                                <li
                                    key={assignment.subject.public_id}
                                    className="px-5 py-3"
                                >
                                    <p className="text-sm font-medium text-slate-900">
                                        {assignment.subject.name}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {assignment.teacher.name}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    )}
                    {unassigned.length > 0 && (
                        <div className="border-t border-slate-200 p-5">
                            <p className="mb-2 text-xs font-medium text-slate-500">
                                Belum ada guru:
                            </p>
                            <div className="flex flex-wrap gap-1">
                                {unassigned.map((subject) => (
                                    <StatusBadge
                                        key={subject.public_id}
                                        variant="warning"
                                    >
                                        {subject.name}
                                    </StatusBadge>
                                ))}
                            </div>
                        </div>
                    )}
                </section>
            </div>

            <ClassroomFormDialog
                open={editing}
                onOpenChange={setEditing}
                classroom={classroom}
                grades={grades}
                teachers={teachers}
            />
            <EnrollStudentsDialog
                open={enrolling}
                onOpenChange={setEnrolling}
                classroomPublicId={classroom.public_id}
                classroomName={classroom.name}
            />
            <ConfirmDialog
                open={removing !== null}
                onOpenChange={(open) => !open && setRemoving(null)}
                variant="danger"
                title={`Keluarkan ${removing?.name ?? ''}?`}
                description="Gunakan untuk koreksi salah input. Untuk memindahkan siswa, tambahkan ke rombel tujuan dengan opsi pindahkan."
                confirmLabel="Ya, keluarkan"
                processing={processing}
                onConfirm={removeStudent}
            />
            <ConfirmDialog
                open={deleting}
                onOpenChange={setDeleting}
                variant="danger"
                title={`Hapus rombel ${classroom.name}?`}
                description="Rombel yang sudah memiliki riwayat siswa tidak dapat dihapus."
                confirmLabel="Ya, hapus"
                processing={processing}
                onConfirm={destroy}
            />
        </AppLayout>
    );
}
