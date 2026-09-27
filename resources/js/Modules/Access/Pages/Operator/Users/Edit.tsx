import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound, Power } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime } from '@/lib/format';
import { PasswordFields } from '@/Modules/Access/Components/PasswordFields';
import { RoleCheckboxes } from '@/Modules/Access/Components/RoleCheckboxes';
import type { UserRow } from '@/Modules/Access/types';
import type { Role } from '@/types';

type Props = {
    user: UserRow;
    roles: Role[];
};

function Card({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-base font-semibold text-slate-900">{title}</h2>
            {description && (
                <p className="mt-1 text-sm text-slate-500">{description}</p>
            )}
            <div className="mt-5">{children}</div>
        </section>
    );
}

export default function UsersEdit({ user, roles }: Props) {
    const base = `/operator/users/${user.public_id}`;

    const profile = useForm({
        name: user.name,
        email: user.email,
        roles: user.roles.map((role): string => role.code),
    });

    const password = useForm({ password: '', password_confirmation: '' });
    const status = useForm({ is_active: !user.is_active });
    const [confirmStatus, setConfirmStatus] = useState(false);

    const saveProfile = (event: FormEvent) => {
        event.preventDefault();
        profile.put(base, { preserveScroll: true });
    };

    const savePassword = (event: FormEvent) => {
        event.preventDefault();
        password.put(`${base}/password`, {
            preserveScroll: true,
            onSuccess: () => password.reset(),
        });
    };

    const toggleStatus = () => {
        // Nilai dihitung saat submit dari props terbaru (bukan state awal form).
        status.transform(() => ({ is_active: !user.is_active }));
        status.patch(`${base}/status`, {
            preserveScroll: true,
            onFinish: () => setConfirmStatus(false),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'User Management', href: '/operator/users' },
                { title: user.name, href: `${base}/edit` },
            ]}
        >
            <Head title={`Ubah ${user.name}`} />

            <PageHeader
                title={user.name}
                description={
                    user.last_login_at
                        ? `Login terakhir ${formatDateTime(user.last_login_at)}`
                        : 'Belum pernah login'
                }
                actions={
                    user.is_active ? (
                        <StatusBadge variant="success" dot>
                            Aktif
                        </StatusBadge>
                    ) : (
                        <StatusBadge variant="neutral" dot>
                            Nonaktif
                        </StatusBadge>
                    )
                }
            />

            <div className="grid max-w-2xl gap-6">
                <Card
                    title="Data akun"
                    description="Nama, email, dan role pengguna."
                >
                    <form onSubmit={saveProfile} className="space-y-5">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama lengkap</Label>
                                <Input
                                    id="name"
                                    value={profile.data.name}
                                    onChange={(event) =>
                                        profile.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!profile.errors.name}
                                />
                                <InputError message={profile.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={profile.data.email}
                                    onChange={(event) =>
                                        profile.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!profile.errors.email}
                                />
                                <InputError message={profile.errors.email} />
                            </div>
                        </div>

                        <RoleCheckboxes
                            roles={roles}
                            value={profile.data.roles}
                            onChange={(value) =>
                                profile.setData('roles', value)
                            }
                            error={
                                profile.errors.roles ??
                                profile.errors['roles.0']
                            }
                            locked={user.is_self ? ['operator'] : []}
                        />

                        <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <Button type="button" variant="outline" asChild>
                                <Link href="/operator/users">Kembali</Link>
                            </Button>
                            <Button type="submit" disabled={profile.processing}>
                                {profile.processing && <Spinner />}
                                Simpan perubahan
                            </Button>
                        </div>
                    </form>
                </Card>

                <Card
                    title="Reset password"
                    description="Tetapkan password baru. Pengguna akan dikeluarkan dari semua perangkat."
                >
                    <form onSubmit={savePassword} className="space-y-5">
                        <PasswordFields
                            label="Password baru"
                            password={password.data.password}
                            confirmation={password.data.password_confirmation}
                            onChange={(value, confirmation) =>
                                password.setData({
                                    password: value,
                                    password_confirmation: confirmation,
                                })
                            }
                            error={password.errors.password}
                        />
                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                variant="secondary"
                                disabled={password.processing}
                            >
                                {password.processing ? (
                                    <Spinner />
                                ) : (
                                    <KeyRound />
                                )}
                                Reset password
                            </Button>
                        </div>
                    </form>
                </Card>

                <Card
                    title="Status akun"
                    description={
                        user.is_self
                            ? 'Anda tidak dapat menonaktifkan akun Anda sendiri.'
                            : user.is_active
                              ? 'Pengguna nonaktif tidak dapat masuk dan akan dikeluarkan dari semua perangkat.'
                              : 'Aktifkan kembali agar pengguna dapat masuk.'
                    }
                >
                    <Button
                        type="button"
                        variant={user.is_active ? 'destructive' : 'default'}
                        disabled={user.is_self}
                        onClick={() => setConfirmStatus(true)}
                    >
                        <Power />
                        {user.is_active ? 'Nonaktifkan akun' : 'Aktifkan akun'}
                    </Button>
                    <InputError
                        message={status.errors.is_active}
                        className="mt-2"
                    />
                </Card>
            </div>

            <ConfirmDialog
                open={confirmStatus}
                onOpenChange={setConfirmStatus}
                variant={user.is_active ? 'danger' : 'default'}
                title={
                    user.is_active
                        ? `Nonaktifkan ${user.name}?`
                        : `Aktifkan ${user.name}?`
                }
                description={
                    user.is_active
                        ? 'Pengguna tidak dapat masuk sampai diaktifkan kembali.'
                        : 'Pengguna dapat masuk kembali dengan password terakhirnya.'
                }
                confirmLabel={
                    user.is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan'
                }
                processing={status.processing}
                onConfirm={toggleStatus}
            />
        </AppLayout>
    );
}
