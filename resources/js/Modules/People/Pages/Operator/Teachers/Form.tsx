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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { toastFirstError } from '@/lib/toast-errors';
import { AccountSection } from '@/Modules/People/Components/AccountSection';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import { PhotoField } from '@/Modules/People/Components/PhotoField';
import type { Option, TeacherRow } from '@/Modules/People/types';

type Props = {
    teacher: TeacherRow | null;
    options: { genders: Option[]; employment: Option[] };
};

const BASE = '/operator/master-data/guru';

export default function TeacherForm({ teacher, options }: Props) {
    const isEdit = teacher !== null;
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        name: teacher?.name ?? '',
        nip: teacher?.nip ?? '',
        nuptk: teacher?.nuptk ?? '',
        gender: teacher?.gender.value ?? '',
        phone: teacher?.phone ?? '',
        employment_status: teacher?.employment_status.value ?? '',
        is_active: teacher?.is_active ?? true,
        photo: null as File | null,
        remove_photo: false,
        create_account: false,
        account_email: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(isEdit ? `${BASE}/${teacher.public_id}` : BASE, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({
                    ...data,
                    photo: null,
                    remove_photo: false,
                })),
        });
    };

    const destroy = () => {
        if (!teacher) {
            return;
        }

        router.delete(`${BASE}/${teacher.public_id}`, {
            onStart: () => setDeleting(true),
            onError: (errors) => {
                setConfirmDelete(false);
                toastFirstError(errors);
            },
            onFinish: () => setDeleting(false),
        });
    };

    const title = isEdit ? teacher.name : 'Tambah guru';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Guru', href: BASE },
                {
                    title: isEdit ? 'Ubah' : 'Tambah',
                    href: isEdit
                        ? `${BASE}/${teacher.public_id}/ubah`
                        : `${BASE}/tambah`,
                },
            ]}
        >
            <Head title={title} />

            <PageHeader
                title={title}
                description={
                    isEdit
                        ? 'Perbarui data induk guru.'
                        : 'Isi data induk guru. Akun login bisa dibuat sekaligus.'
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
                    <FormCard title="Data induk">
                        <FormField
                            id="name"
                            label="Nama lengkap (dengan gelar)"
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

                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                id="nip"
                                label="NIP"
                                error={form.errors.nip}
                                hint="18 digit, opsional"
                            >
                                <Input
                                    id="nip"
                                    inputMode="numeric"
                                    maxLength={18}
                                    value={form.data.nip}
                                    onChange={(event) =>
                                        form.setData('nip', event.target.value)
                                    }
                                    aria-invalid={!!form.errors.nip}
                                />
                            </FormField>
                            <FormField
                                id="nuptk"
                                label="NUPTK"
                                error={form.errors.nuptk}
                                hint="16 digit, opsional"
                            >
                                <Input
                                    id="nuptk"
                                    inputMode="numeric"
                                    maxLength={16}
                                    value={form.data.nuptk}
                                    onChange={(event) =>
                                        form.setData(
                                            'nuptk',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!form.errors.nuptk}
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                id="gender"
                                label="Jenis kelamin"
                                error={form.errors.gender}
                            >
                                <Select
                                    value={form.data.gender}
                                    onValueChange={(value) =>
                                        form.setData('gender', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="gender"
                                        aria-invalid={!!form.errors.gender}
                                    >
                                        <SelectValue placeholder="Pilih" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.genders.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                            <FormField
                                id="employment_status"
                                label="Status kepegawaian"
                                error={form.errors.employment_status}
                            >
                                <Select
                                    value={form.data.employment_status}
                                    onValueChange={(value) =>
                                        form.setData('employment_status', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="employment_status"
                                        aria-invalid={
                                            !!form.errors.employment_status
                                        }
                                    >
                                        <SelectValue placeholder="Pilih" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.employment.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {option.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </FormField>
                        </div>

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
                            id="photo"
                            label="Foto"
                            error={form.errors.photo}
                        >
                            <PhotoField
                                name={form.data.name || 'Guru'}
                                currentUrl={teacher?.photo_url}
                                file={form.data.photo}
                                remove={form.data.remove_photo}
                                onFileChange={(file) =>
                                    form.setData('photo', file)
                                }
                                onRemoveChange={(remove) =>
                                    form.setData('remove_photo', remove)
                                }
                                error={form.errors.photo}
                            />
                        </FormField>

                        <label className="flex items-center gap-3 text-sm text-slate-700">
                            <Checkbox
                                checked={form.data.is_active}
                                onCheckedChange={(checked) =>
                                    form.setData('is_active', checked === true)
                                }
                            />
                            Guru aktif mengajar
                        </label>
                    </FormCard>

                    {!isEdit && (
                        <FormCard
                            title="Akun login"
                            description="Opsional. Password sementara dibuat otomatis dan ditampilkan sekali."
                        >
                            <label className="flex items-center gap-3 text-sm text-slate-700">
                                <Checkbox
                                    checked={form.data.create_account}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'create_account',
                                            checked === true,
                                        )
                                    }
                                />
                                Buat akun login guru sekarang
                            </label>
                            {form.data.create_account && (
                                <FormField
                                    id="account_email"
                                    label="Email akun"
                                    error={form.errors.account_email}
                                >
                                    <Input
                                        id="account_email"
                                        type="email"
                                        value={form.data.account_email}
                                        onChange={(event) =>
                                            form.setData(
                                                'account_email',
                                                event.target.value,
                                            )
                                        }
                                        aria-invalid={
                                            !!form.errors.account_email
                                        }
                                    />
                                </FormField>
                            )}
                        </FormCard>
                    )}

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href={BASE}>Kembali</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {isEdit ? 'Simpan perubahan' : 'Simpan guru'}
                        </Button>
                    </div>
                </form>

                {isEdit && (
                    <AccountSection
                        account={teacher.account ?? null}
                        linkUrl={`${BASE}/${teacher.public_id}/akun`}
                    />
                )}
            </div>

            <ConfirmDialog
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                variant="danger"
                title={`Hapus data ${teacher?.name ?? 'guru'}?`}
                description="Data guru dipindahkan ke tempat sampah. Akun login tidak ikut dihapus."
                confirmLabel="Ya, hapus"
                processing={deleting}
                onConfirm={destroy}
            />
            <CredentialsDialog />
        </AppLayout>
    );
}
