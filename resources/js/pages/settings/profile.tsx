import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import RoleLayout from '@/Layouts/RoleLayout';
import SettingsLayout from '@/Layouts/settings/layout';

export default function Profile() {
    const { auth } = usePage().props;
    // Halaman ini berada di balik middleware auth.
    const user = auth.user!;

    const form = useForm({ name: user.name, email: user.email });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.patch('/settings/profile', { preserveScroll: true });
    };

    return (
        <RoleLayout
            breadcrumbs={[
                { title: 'Pengaturan Akun', href: '/settings/profile' },
            ]}
        >
            <Head title="Profil" />

            <SettingsLayout>
                <h2 className="text-base font-semibold text-slate-900">
                    Informasi profil
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Perbarui nama dan alamat email akun Anda.
                </p>

                <form onSubmit={submit} className="mt-6 space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama lengkap</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            autoComplete="name"
                            aria-invalid={!!form.errors.name}
                            required
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
                            autoComplete="username"
                            aria-invalid={!!form.errors.email}
                            required
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan perubahan
                    </Button>
                </form>
            </SettingsLayout>
        </RoleLayout>
    );
}
