import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import PasswordInput from '@/Components/password-input';
import TextLink from '@/Components/text-link';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { portals } from '@/config/portals';
import AuthLayout from '@/Layouts/AuthLayout';
import type { RoleCode } from '@/types';

type Props = {
    portal: RoleCode;
    portalSlug: string;
};

export default function Login({ portal, portalSlug }: Props) {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/login/${portalSlug}`, {
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <AuthLayout
            portal={portal}
            title={`Masuk ke ${portals[portal].title}`}
            description="Gunakan email dan password yang diberikan sekolah."
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
                        autoComplete="username"
                        autoFocus
                        placeholder="nama@sekolah.sch.id"
                        aria-invalid={!!form.errors.email}
                        required
                    />
                    <InputError message={form.errors.email} />
                </div>

                <div className="grid gap-2">
                    <div className="flex items-center justify-between">
                        <Label htmlFor="password">Password</Label>
                        <TextLink href="/forgot-password" className="text-xs">
                            Lupa password?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        value={form.data.password}
                        onChange={(event) =>
                            form.setData('password', event.target.value)
                        }
                        autoComplete="current-password"
                        aria-invalid={!!form.errors.password}
                        required
                    />
                    <InputError message={form.errors.password} />
                </div>

                <div className="flex items-center gap-2">
                    <Checkbox
                        id="remember"
                        checked={form.data.remember}
                        onCheckedChange={(checked) =>
                            form.setData('remember', checked === true)
                        }
                    />
                    <Label
                        htmlFor="remember"
                        className="font-normal text-slate-600"
                    >
                        Ingat saya di perangkat ini
                    </Label>
                </div>

                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                    data-test="login-button"
                >
                    {form.processing && <Spinner />}
                    Masuk
                </Button>
            </form>

            <Link
                href="/login"
                className="mt-8 inline-flex items-center gap-1.5 text-sm text-slate-500 transition-colors duration-150 hover:text-brand-700"
            >
                <ArrowLeft className="size-4" />
                Pilih portal lain
            </Link>
        </AuthLayout>
    );
}
