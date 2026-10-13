import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import PasswordInput from '@/Components/password-input';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import RoleLayout from '@/Layouts/RoleLayout';
import SettingsLayout from '@/Layouts/settings/layout';

type Props = {
    /** Akun berpassword awal: wajib ganti sebelum memakai aplikasi. */
    mustChangePassword: boolean;
};

export default function Security({ mustChangePassword }: Props) {
    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/settings/password', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <RoleLayout
            breadcrumbs={[
                { title: 'Pengaturan Akun', href: '/settings/profile' },
            ]}
        >
            <Head title="Keamanan" />

            <SettingsLayout>
                {mustChangePassword && (
                    <p
                        role="alert"
                        className="mb-6 flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-900"
                    >
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <span>
                            <strong>Ganti password awal Anda.</strong> Akun ini
                            masih memakai password bawaan dari sekolah. Menu
                            lain bisa dibuka setelah password diganti.
                        </span>
                    </p>
                )}
                <h2 className="text-base font-semibold text-slate-900">
                    Ganti password
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Gunakan password yang panjang dan tidak dipakai di layanan
                    lain.
                </p>

                <form onSubmit={submit} className="mt-6 space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="current_password">
                            Password saat ini
                        </Label>
                        <PasswordInput
                            id="current_password"
                            value={form.data.current_password}
                            onChange={(event) =>
                                form.setData(
                                    'current_password',
                                    event.target.value,
                                )
                            }
                            autoComplete="current-password"
                        />
                        <InputError message={form.errors.current_password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Password baru</Label>
                        <PasswordInput
                            id="password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            autoComplete="new-password"
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Ulangi password baru
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            autoComplete="new-password"
                        />
                        <InputError
                            message={form.errors.password_confirmation}
                        />
                    </div>

                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan password
                    </Button>
                </form>
            </SettingsLayout>
        </RoleLayout>
    );
}
