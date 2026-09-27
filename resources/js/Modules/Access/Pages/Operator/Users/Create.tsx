import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { PasswordFields } from '@/Modules/Access/Components/PasswordFields';
import { RoleCheckboxes } from '@/Modules/Access/Components/RoleCheckboxes';
import type { Role } from '@/types';

type Props = {
    roles: Role[];
};

export default function UsersCreate({ roles }: Props) {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        roles: [] as string[],
        is_active: true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/operator/users');
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'User Management', href: '/operator/users' },
                { title: 'Tambah', href: '/operator/users/create' },
            ]}
        >
            <Head title="Tambah Pengguna" />

            <PageHeader
                title="Tambah pengguna"
                description="Buat akun baru dan tentukan role-nya."
            />

            <form
                onSubmit={submit}
                className="max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div className="grid gap-5 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama lengkap</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            aria-invalid={!!form.errors.name}
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            aria-invalid={!!form.errors.email}
                        />
                        <InputError message={form.errors.email} />
                    </div>
                </div>

                <PasswordFields
                    label="Password awal"
                    password={form.data.password}
                    confirmation={form.data.password_confirmation}
                    onChange={(password, confirmation) =>
                        form.setData((data) => ({
                            ...data,
                            password,
                            password_confirmation: confirmation,
                        }))
                    }
                    error={form.errors.password}
                />

                <RoleCheckboxes
                    roles={roles}
                    value={form.data.roles}
                    onChange={(value) => form.setData('roles', value)}
                    error={form.errors.roles ?? form.errors['roles.0']}
                />

                <label className="flex items-center gap-3 text-sm text-slate-700">
                    <Checkbox
                        checked={form.data.is_active}
                        onCheckedChange={(checked) =>
                            form.setData('is_active', checked === true)
                        }
                    />
                    Akun langsung aktif
                </label>

                <div className="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <Button type="button" variant="outline" asChild>
                        <Link href="/operator/users">Batal</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan pengguna
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
