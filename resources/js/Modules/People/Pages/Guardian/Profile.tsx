import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChevronRight, KeyRound, UserRoundX } from 'lucide-react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import ParentLayout from '@/Layouts/ParentLayout';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import type { GuardianRow } from '@/Modules/People/types';

type Props = {
    guardian: GuardianRow | null;
};

export default function GuardianProfile({ guardian }: Props) {
    const { auth } = usePage().props;

    const form = useForm({
        phone: guardian?.phone ?? '',
        occupation: guardian?.occupation ?? '',
        address: guardian?.address ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/orang-tua/profil', { preserveScroll: true });
    };

    return (
        <ParentLayout>
            <Head title="Profil" />

            <div>
                <h1 className="text-xl font-semibold text-slate-900">Profil</h1>
                <p className="text-sm text-slate-500">{auth.user?.email}</p>
            </div>

            {!guardian ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Data orang tua belum ditautkan"
                        description="Akun Anda belum terhubung dengan data orang tua. Hubungi operator sekolah."
                    />
                </div>
            ) : (
                <form onSubmit={submit}>
                    <FormCard
                        title={guardian.name}
                        description="Perbarui kontak agar sekolah mudah menghubungi Anda."
                    >
                        <FormField
                            id="phone"
                            label="Telepon / WhatsApp"
                            error={form.errors.phone}
                        >
                            <Input
                                id="phone"
                                inputMode="tel"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                                aria-invalid={!!form.errors.phone}
                            />
                        </FormField>
                        <FormField
                            id="occupation"
                            label="Pekerjaan"
                            error={form.errors.occupation}
                        >
                            <Input
                                id="occupation"
                                value={form.data.occupation}
                                onChange={(event) =>
                                    form.setData(
                                        'occupation',
                                        event.target.value,
                                    )
                                }
                            />
                        </FormField>
                        <FormField
                            id="address"
                            label="Alamat"
                            error={form.errors.address}
                        >
                            <textarea
                                id="address"
                                rows={3}
                                value={form.data.address}
                                onChange={(event) =>
                                    form.setData('address', event.target.value)
                                }
                                className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                        </FormField>
                        <Button
                            type="submit"
                            className="w-full sm:w-auto"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Simpan profil
                        </Button>
                    </FormCard>
                </form>
            )}

            <Link
                href="/settings/security"
                className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300"
            >
                <span className="flex size-10 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                    <KeyRound className="size-5" />
                </span>
                <span className="flex-1 text-sm font-medium text-slate-900">
                    Ganti password
                </span>
                <ChevronRight className="size-4 text-slate-400" />
            </Link>
        </ParentLayout>
    );
}
