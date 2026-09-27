import { Head, Link } from '@inertiajs/react';
import { Baby, ChevronRight } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import ParentLayout from '@/Layouts/ParentLayout';
import { StudentStatusBadge } from '@/Modules/People/Components/StudentStatusBadge';
import type { StudentRow } from '@/Modules/People/types';

type Props = {
    students: { data: StudentRow[] };
};

export default function ChildrenIndex({ students }: Props) {
    return (
        <ParentLayout>
            <Head title="Anak Saya" />

            <div>
                <h1 className="text-xl font-semibold text-slate-900">
                    Anak Saya
                </h1>
                <p className="text-sm text-slate-500">
                    Siswa yang terhubung dengan akun Anda.
                </p>
            </div>

            {students.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Baby}
                        title="Belum ada anak terhubung"
                        description="Hubungi operator sekolah untuk menautkan data anak ke akun Anda."
                    />
                </div>
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2">
                    {students.data.map((child) => (
                        <li key={child.public_id}>
                            <Link
                                href={`/orang-tua/anak/${child.public_id}`}
                                className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300"
                            >
                                <PersonAvatar
                                    name={child.name}
                                    photoUrl={child.photo_url}
                                    className="size-12"
                                />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate font-semibold text-slate-900">
                                        {child.name}
                                    </p>
                                    <p className="text-xs text-slate-500 tabular-nums">
                                        NIS {child.nis}
                                    </p>
                                    <div className="mt-1">
                                        <StudentStatusBadge
                                            status={child.status}
                                        />
                                    </div>
                                </div>
                                <ChevronRight className="size-5 text-slate-400" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </ParentLayout>
    );
}
