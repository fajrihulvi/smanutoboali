import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound, UserRoundX } from 'lucide-react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { SignatureField } from '@/Components/shared/SignatureField';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import { PhotoField } from '@/Modules/People/Components/PhotoField';
import type { TeacherRow } from '@/Modules/People/types';

type Props = {
    teacher: TeacherRow | null;
    signatureUrl: string | null;
};

function ReadOnly({ label, value }: { label: string; value: string | null }) {
    return (
        <div>
            <dt className="text-xs text-slate-500">{label}</dt>
            <dd className="mt-0.5 text-sm font-medium text-slate-900 tabular-nums">
                {value ?? '—'}
            </dd>
        </div>
    );
}

export default function TeacherProfile({ teacher, signatureUrl }: Props) {
    const form = useForm({
        phone: teacher?.phone ?? '',
        photo: null as File | null,
        remove_photo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/guru/profil', {
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

    return (
        <AppLayout breadcrumbs={[{ title: 'Profil', href: '/guru/profil' }]}>
            <Head title="Profil Guru" />

            <PageHeader
                title="Profil Guru"
                description="Data induk dikelola operator. Anda dapat memperbarui kontak dan foto."
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/settings/security">
                            <KeyRound />
                            Ganti password
                        </Link>
                    </Button>
                }
            />

            {!teacher ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Data guru belum ditautkan"
                        description="Akun Anda belum terhubung dengan data guru. Hubungi operator sekolah."
                    />
                </div>
            ) : (
                <div className="grid max-w-3xl gap-6">
                    <FormCard title="Data induk">
                        <div className="flex items-center gap-4">
                            <PersonAvatar
                                name={teacher.name}
                                photoUrl={teacher.photo_url}
                                className="size-16"
                            />
                            <div>
                                <p className="text-base font-semibold text-slate-900">
                                    {teacher.name}
                                </p>
                                <p className="text-sm text-slate-500">
                                    {teacher.employment_status.label}
                                </p>
                            </div>
                        </div>
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <ReadOnly label="NIP" value={teacher.nip} />
                            <ReadOnly label="NUPTK" value={teacher.nuptk} />
                            <ReadOnly
                                label="Jenis kelamin"
                                value={teacher.gender.label}
                            />
                        </dl>
                    </FormCard>

                    <form onSubmit={submit}>
                        <FormCard title="Kontak & foto">
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
                                id="photo"
                                label="Foto"
                                error={form.errors.photo}
                            >
                                <PhotoField
                                    name={teacher.name}
                                    currentUrl={teacher.photo_url}
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
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    Simpan profil
                                </Button>
                            </div>
                        </FormCard>
                    </form>

                    <FormCard
                        title="Tanda tangan"
                        description="Dipakai di PDF raport bila Anda menjadi wali kelas. Tanpa gambar, tersedia ruang untuk tanda tangan basah."
                    >
                        <SignatureField
                            url={signatureUrl}
                            endpoint="/guru/profil/tanda-tangan"
                        />
                    </FormCard>
                </div>
            )}
        </AppLayout>
    );
}
