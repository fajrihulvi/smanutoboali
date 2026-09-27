import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import AuthLayout from '@/Layouts/AuthLayout';

export default function ForgotPassword() {
    const form = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/forgot-password', { preserveScroll: true });
    };

    return (
        <AuthLayout
            title="Lupa password"
            description="Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password."
        >
            <form onSubmit={submit} className="space-y-5" noValidate>
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        value={form.data.email}
                        onChange={(event) =>
                            form.setData('email', event.target.value)
                        }
                        autoComplete="email"
                        autoFocus
                        aria-invalid={!!form.errors.email}
                        required
                    />
                    <InputError message={form.errors.email} />
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    Kirim tautan reset
                </Button>
            </form>

            <Link
                href="/login"
                className="mt-8 inline-flex items-center gap-1.5 text-sm text-slate-500 transition-colors duration-150 hover:text-brand-700"
            >
                <ArrowLeft className="size-4" />
                Kembali ke halaman masuk
            </Link>
        </AuthLayout>
    );
}
