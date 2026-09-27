import { Head, Link } from '@inertiajs/react';
import { ChevronRight, ShieldCheck, Users } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import AppLayout from '@/Layouts/AppLayout';
import { formatNumber } from '@/lib/format';
import type { RoleSummary } from '@/Modules/Access/types';

type Props = {
    roles: { data: RoleSummary[] };
    totalPermissions: number;
};

export default function RolesIndex({ roles, totalPermissions }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Role & Permission', href: '/operator/roles' },
            ]}
        >
            <Head title="Role & Permission" />

            <PageHeader
                title="Role & Permission"
                description="Atur hak akses setiap role. Role inti tidak dapat dihapus."
            />

            {roles.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={ShieldCheck}
                        title="Belum ada role"
                        description="Jalankan seeder RolesAndPermissionsSeeder."
                    />
                </div>
            ) : (
                <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {roles.data.map((role) => (
                        <li key={role.code}>
                            <Link
                                href={`/operator/roles/${role.code}/edit`}
                                className="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-colors duration-150 hover:border-brand-300"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <span className="flex size-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                                        <ShieldCheck className="size-5" />
                                    </span>
                                    {role.is_core && (
                                        <StatusBadge variant="brand">
                                            Role inti
                                        </StatusBadge>
                                    )}
                                </div>
                                <h2 className="mt-4 text-base font-semibold text-slate-900">
                                    {role.label}
                                </h2>
                                <p className="text-xs text-slate-500">
                                    {role.code}
                                </p>

                                <dl className="mt-5 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt className="text-xs text-slate-500">
                                            Permission
                                        </dt>
                                        <dd className="font-semibold text-slate-900 tabular-nums">
                                            {role.permissions_count}
                                            <span className="font-normal text-slate-400">
                                                {' '}
                                                / {totalPermissions}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="flex items-center gap-1 text-xs text-slate-500">
                                            <Users className="size-3.5" />
                                            Pengguna
                                        </dt>
                                        <dd className="font-semibold text-slate-900 tabular-nums">
                                            {formatNumber(role.users_count)}
                                        </dd>
                                    </div>
                                </dl>

                                <span className="mt-5 inline-flex items-center gap-1 text-sm font-medium text-brand-600 group-hover:text-brand-700">
                                    Atur permission
                                    <ChevronRight className="size-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
