import { Head, Link, router, useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { CredentialsDialog } from '@/Components/shared/CredentialsDialog';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { toastFirstError } from '@/lib/toast-errors';
import { AccountSection } from '@/Modules/People/Components/AccountSection';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import type { GuardianRow } from '@/Modules/People/types';

type Props = {
    guardian: GuardianRow | null;
};

const BASE = '/operator/master-data/orang-tua';

const textareaClass =
    'w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function GuardianForm({ guardian }: Props) {
    const isEdit = guardian !== null;
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        name: guardian?.name ?? '',
        nik: '',
        phone: guardian?.phone ?? '',
        email: guardian?.email ?? '',
        occupation: guardian?.occupation ?? '',
        address: guardian?.address ?? '',
        create_account: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => form.setData('nik', ''),
        };

        if (isEdit) {
            form.put(`${BASE}/${guardian.public_id}`, options);
        } else {
            form.post(BASE, options);
        }
    };

    const destroy = () => {
        if (!guardian) {
            return;
        }

        router.delete(`${BASE}/${guardian.public_id}`, {
            onStart: () => setDeleting(true),
            onError: (errors) => {
                setConfirmDelete(false);
                toastFirstError(errors);
            },
            onFinish: () => setDeleting(false),
        });
    };

    const title = isEdit ? guardian.name : 'Tambah orang tua';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Orang Tua', href: BASE },
                {
                    title: isEdit ? 'Ubah' : 'Tambah',
                    href: isEdit
                        ? `${BASE}/${guardian.public_id}/ubah`
                        : `${BASE}/tambah`,
                },
            ]}
        >
            <Head title={title} />

            <PageHeader
                title={title}
                description={
                    isEdit
                        ? 'Perbarui data orang tua/wali.'
                        : 'Isi data orang tua/wali. Akun portal orang tua bisa dibuat sekaligus.'
                }
                actions={
                    isEdit && (
                        <Button
                            variant="outline"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() => setConfirmDelete(true)}
                        >
                            <Trash2 />
                            Hapus
                        </Button>
                    )
                }
            />

            <div className="grid max-w-3xl gap-6">
                <form onSubmit={submit} className="space-y-6">
                    <FormCard title="Data orang tua">
                        <FormField
                            id="name"
                            label="Nama lengkap"
                            error={form.errors.name}
                        >
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                aria-invalid={!!form.errors.name}
                            />
                        </FormField>

                        <FormField
                            id="nik"
                            label="NIK"
                            error={form.errors.nik}
                            hint={
                                guardian?.nik_masked
                                    ? `Tersimpan: ${guardian.nik_masked}. Kosongkan bila tidak diubah.`
                                    : '16 digit, opsional. Disimpan terenkripsi.'
                            }
                        >
                            <Input
                                id="nik"
                                inputMode="numeric"
                                maxLength={16}
                                autoComplete="off"
                                value={form.data.nik}
                                onChange={(event) =>
                                    form.setData('nik', event.target.value)
                                }
                                aria-invalid={!!form.errors.nik}
                            />
                        </FormField>

                        <div className="grid gap-5 sm:grid-cols-2">
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
                                        form.setData(
                                            'phone',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!form.errors.phone}
                                />
                            </FormField>
                            <FormField
                                id="email"
                                label="Email"
                                error={form.errors.email}
                            >
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!form.errors.email}
                                />
                            </FormField>
                        </div>

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
                                className={textareaClass}
                            />
                        </FormField>

                        {!isEdit && (
                            <label className="flex items-start gap-3 text-sm text-slate-700">
                                <Checkbox
                                    className="mt-0.5"
                                    checked={form.data.create_account}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'create_account',
                                            checked === true,
                                        )
                                    }
                                />
                                <span>
                                    Buat akun portal orang tua dengan email di
                                    atas
                                    <span className="block text-xs text-slate-500">
                                        Password sementara dibuat otomatis dan
                                        ditampilkan sekali.
                                    </span>
                                </span>
                            </label>
                        )}
                    </FormCard>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href={BASE}>Kembali</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {isEdit ? 'Simpan perubahan' : 'Simpan orang tua'}
                        </Button>
                    </div>
                </form>

                {isEdit && (
                    <AccountSection
                        account={guardian.account ?? null}
                        linkUrl={`${BASE}/${guardian.public_id}/akun`}
                        defaultEmail={guardian.email}
                    />
                )}
            </div>

            <ConfirmDialog
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                variant="danger"
                title={`Hapus data ${guardian?.name ?? 'orang tua'}?`}
                description="Data orang tua dipindahkan ke tempat sampah. Akun login tidak ikut dihapus."
                confirmLabel="Ya, hapus"
                processing={deleting}
                onConfirm={destroy}
            />
            <CredentialsDialog />
        </AppLayout>
    );
}
