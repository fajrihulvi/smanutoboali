import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import PasswordInput from '@/Components/password-input';
import TextLink from '@/Components/text-link';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import AuthLayout from '@/Layouts/AuthLayout';

export default function RegisterApplicant() {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/ppdb/daftar', {
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            portal="applicant"
            title="Buat akun PPDB"
            description="Satu akun bisa dipakai untuk mendaftarkan lebih dari satu calon siswa."
        >
            <form onSubmit={submit} className="space-y-5" noValidate>
                <div className="grid gap-2">
                    <Label htmlFor="name">Nama lengkap orang tua/wali</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        autoComplete="name"
                        autoFocus
                        aria-invalid={!!form.errors.name}
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        autoComplete="email"
                        placeholder="nama@email.com"
                        aria-invalid={!!form.errors.email}
                    />
                    <p className="text-xs text-slate-500">
                        Dipakai untuk masuk dan menerima kabar pendaftaran.
                    </p>
                    <InputError message={form.errors.email} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <PasswordInput
                        id="password"
                        value={form.data.password}
                        onChange={(e) =>
                            form.setData('password', e.target.value)
                        }
                        autoComplete="new-password"
                        aria-invalid={!!form.errors.password}
                    />
                    <InputError message={form.errors.password} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Ulangi password
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        value={form.data.password_confirmation}
                        onChange={(e) =>
                            form.setData(
                                'password_confirmation',
                                e.target.value,
                            )
                        }
                        autoComplete="new-password"
                    />
                </div>
                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    Buat akun
                </Button>
            </form>
            <p className="mt-6 text-center text-sm text-slate-600">
                Sudah punya akun? <TextLink href="/ppdb/masuk">Masuk</TextLink>
            </p>
        </AuthLayout>
    );
}
