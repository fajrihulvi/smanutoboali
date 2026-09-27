import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { StudentStatusBadge } from '@/Modules/People/Components/StudentStatusBadge';
import type { StudentRow } from '@/Modules/People/types';

type Props = {
    child: StudentRow;
};

function Item({
    label,
    value,
}: {
    label: string;
    value: string | null | undefined;
}) {
    return (
        <div className="flex justify-between gap-4 py-3">
            <dt className="text-sm text-slate-500">{label}</dt>
            <dd className="text-right text-sm font-medium text-slate-900">
                {value || '—'}
            </dd>
        </div>
    );
}

export default function ChildShow({ child }: Props) {
    const birth = [
        child.birth_place,
        child.birth_date ? formatDate(child.birth_date) : null,
    ]
        .filter(Boolean)
        .join(', ');

    return (
        <ParentLayout>
            <Head title={child.name} />

            <Link
                href="/orang-tua/anak"
                className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-brand-700"
            >
                <ArrowLeft className="size-4" />
                Anak Saya
            </Link>

            <section className="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <PersonAvatar
                    name={child.name}
                    photoUrl={child.photo_url}
                    className="size-16"
                />
                <div className="min-w-0">
                    <h1 className="text-lg font-semibold text-slate-900">
                        {child.name}
                    </h1>
                    <p className="text-xs text-slate-500 tabular-nums">
                        NIS {child.nis}
                        {child.nisn && ` · NISN ${child.nisn}`}
                    </p>
                    <div className="mt-1.5">
                        <StudentStatusBadge status={child.status} />
                    </div>
                </div>
            </section>

            <section className="rounded-2xl border border-slate-200 bg-white px-5 shadow-sm">
                <dl className="divide-y divide-slate-100">
                    <Item label="Jenis kelamin" value={child.gender.label} />
                    <Item label="Tempat, tanggal lahir" value={birth} />
                    <Item label="Agama" value={child.religion?.label} />
                    <Item
                        label="Tanggal masuk"
                        value={
                            child.entry_date
                                ? formatDate(child.entry_date)
                                : null
                        }
                    />
                    <Item label="Alamat" value={child.address} />
                </dl>
            </section>

            {child.guardians && child.guardians.length > 0 && (
                <section className="rounded-2xl border border-slate-200 bg-white px-5 shadow-sm">
                    <h2 className="pt-4 text-sm font-semibold text-slate-900">
                        Orang tua / wali terdaftar
                    </h2>
                    <dl className="divide-y divide-slate-100">
                        {child.guardians.map((guardian) => (
                            <Item
                                key={guardian.public_id}
                                label={
                                    guardian.relationship.label +
                                    (guardian.is_primary ? ' (utama)' : '')
                                }
                                value={guardian.name}
                            />
                        ))}
                    </dl>
                </section>
            )}
        </ParentLayout>
    );
}
