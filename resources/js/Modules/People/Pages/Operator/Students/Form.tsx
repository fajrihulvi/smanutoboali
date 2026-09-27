import { Head, Link, router, useForm } from '@inertiajs/react';
import { History, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
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
import { cn } from '@/lib/utils';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import { PhotoField } from '@/Modules/People/Components/PhotoField';
import { StudentGuardiansPanel } from '@/Modules/People/Components/StudentGuardiansPanel';
import { StudentStatusBadge } from '@/Modules/People/Components/StudentStatusBadge';
import type { Option, StudentRow } from '@/Modules/People/types';
import type { StatusVariant } from '@/types';

type Tab = 'identitas' | 'orang-tua' | 'riwayat';

type Enrollment = {
    academic_year: string;
    classroom: string;
    status: Option;
};

const ENROLLMENT_VARIANT: Record<string, StatusVariant> = {
    AKTIF: 'success',
    PINDAH: 'info',
    SELESAI: 'neutral',
    KELUAR: 'danger',
};

type Props = {
    student: StudentRow | null;
    enrollments?: Enrollment[];
    options: {
        genders: Option[];
        religions: Option[];
        statuses: Option[];
        relationships: Option[];
    };
    tab?: Tab;
};

const BASE = '/operator/master-data/siswa';

function EnumSelect({
    id,
    value,
    options,
    placeholder,
    onChange,
    invalid,
}: {
    id: string;
    value: string;
    options: Option[];
    placeholder: string;
    onChange: (value: string) => void;
    invalid?: boolean;
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger id={id} aria-invalid={invalid}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function StudentForm({
    student,
    enrollments = [],
    options,
    tab = 'identitas',
}: Props) {
    const isEdit = student !== null;
    const [activeTab, setActiveTab] = useState<Tab>(isEdit ? tab : 'identitas');
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);

    const form = useForm({
        nis: student?.nis ?? '',
        nisn: student?.nisn ?? '',
        name: student?.name ?? '',
        gender: student?.gender.value ?? '',
        birth_place: student?.birth_place ?? '',
        birth_date: student?.birth_date ?? '',
        religion: student?.religion?.value ?? '',
        address: student?.address ?? '',
        status: student?.status.value ?? 'AKTIF',
        entry_date: student?.entry_date ?? '',
        photo: null as File | null,
        remove_photo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(isEdit ? `${BASE}/${student.public_id}` : BASE, {
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
        if (!student) {
            return;
        }

        router.delete(`${BASE}/${student.public_id}`, {
            onStart: () => setDeleting(true),
            onError: (errors) => {
                setConfirmDelete(false);
                toastFirstError(errors);
            },
            onFinish: () => setDeleting(false),
        });
    };

    const tabs: { key: Tab; label: string }[] = [
        { key: 'identitas', label: 'Identitas' },
        {
            key: 'orang-tua',
            label: `Orang Tua${student?.guardians ? ` (${student.guardians.length})` : ''}`,
        },
        { key: 'riwayat', label: 'Riwayat Kelas' },
    ];

    const title = isEdit ? student.name : 'Tambah siswa';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Siswa', href: BASE },
                {
                    title: isEdit ? student.nis : 'Tambah',
                    href: isEdit
                        ? `${BASE}/${student.public_id}/ubah`
                        : `${BASE}/tambah`,
                },
            ]}
        >
            <Head title={title} />

            <PageHeader
                title={title}
                description={
                    isEdit
                        ? `NIS ${student.nis}`
                        : 'Isi identitas siswa, lalu hubungkan orang tuanya.'
                }
                actions={
                    isEdit && (
                        <>
                            <StudentStatusBadge status={student.status} />
                            <Button
                                variant="outline"
                                className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                onClick={() => setConfirmDelete(true)}
                            >
                                <Trash2 />
                                Hapus
                            </Button>
                        </>
                    )
                }
            />

            {isEdit && (
                <nav
                    className="flex gap-1 overflow-x-auto border-b border-slate-200"
                    aria-label="Bagian data siswa"
                >
                    {tabs.map((item) => (
                        <button
                            key={item.key}
                            type="button"
                            onClick={() => setActiveTab(item.key)}
                            className={cn(
                                '-mb-px border-b-2 px-4 py-2.5 text-sm font-medium whitespace-nowrap transition-colors duration-150',
                                activeTab === item.key
                                    ? 'border-brand-600 text-brand-700'
                                    : 'border-transparent text-slate-500 hover:text-slate-800',
                            )}
                            aria-current={
                                activeTab === item.key ? 'page' : undefined
                            }
                        >
                            {item.label}
                        </button>
                    ))}
                </nav>
            )}

            {activeTab === 'identitas' && (
                <form onSubmit={submit} className="max-w-3xl space-y-6">
                    <FormCard title="Identitas siswa">
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

                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                id="nis"
                                label="NIS"
                                error={form.errors.nis}
                            >
                                <Input
                                    id="nis"
                                    value={form.data.nis}
                                    onChange={(event) =>
                                        form.setData('nis', event.target.value)
                                    }
                                    aria-invalid={!!form.errors.nis}
                                />
                            </FormField>
                            <FormField
                                id="nisn"
                                label="NISN"
                                error={form.errors.nisn}
                                hint="10 digit, opsional"
                            >
                                <Input
                                    id="nisn"
                                    inputMode="numeric"
                                    maxLength={10}
                                    value={form.data.nisn}
                                    onChange={(event) =>
                                        form.setData('nisn', event.target.value)
                                    }
                                    aria-invalid={!!form.errors.nisn}
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-3">
                            <FormField
                                id="gender"
                                label="Jenis kelamin"
                                error={form.errors.gender}
                            >
                                <EnumSelect
                                    id="gender"
                                    value={form.data.gender}
                                    options={options.genders}
                                    placeholder="Pilih"
                                    onChange={(value) =>
                                        form.setData('gender', value)
                                    }
                                    invalid={!!form.errors.gender}
                                />
                            </FormField>
                            <FormField
                                id="religion"
                                label="Agama"
                                error={form.errors.religion}
                            >
                                <EnumSelect
                                    id="religion"
                                    value={form.data.religion}
                                    options={options.religions}
                                    placeholder="Pilih"
                                    onChange={(value) =>
                                        form.setData('religion', value)
                                    }
                                />
                            </FormField>
                            <FormField
                                id="status"
                                label="Status"
                                error={form.errors.status}
                            >
                                <EnumSelect
                                    id="status"
                                    value={form.data.status}
                                    options={options.statuses}
                                    placeholder="Pilih"
                                    onChange={(value) =>
                                        form.setData('status', value)
                                    }
                                    invalid={!!form.errors.status}
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField
                                id="birth_place"
                                label="Tempat lahir"
                                error={form.errors.birth_place}
                            >
                                <Input
                                    id="birth_place"
                                    value={form.data.birth_place}
                                    onChange={(event) =>
                                        form.setData(
                                            'birth_place',
                                            event.target.value,
                                        )
                                    }
                                />
                            </FormField>
                            <FormField
                                id="birth_date"
                                label="Tanggal lahir"
                                error={form.errors.birth_date}
                            >
                                <Input
                                    id="birth_date"
                                    type="date"
                                    value={form.data.birth_date}
                                    onChange={(event) =>
                                        form.setData(
                                            'birth_date',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!form.errors.birth_date}
                                />
                            </FormField>
                        </div>

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

                        <FormField
                            id="entry_date"
                            label="Tanggal masuk"
                            error={form.errors.entry_date}
                        >
                            <Input
                                id="entry_date"
                                type="date"
                                value={form.data.entry_date}
                                onChange={(event) =>
                                    form.setData(
                                        'entry_date',
                                        event.target.value,
                                    )
                                }
                                className="sm:max-w-xs"
                            />
                        </FormField>

                        <FormField
                            id="photo"
                            label="Foto"
                            error={form.errors.photo}
                        >
                            <PhotoField
                                name={form.data.name || 'Siswa'}
                                currentUrl={student?.photo_url}
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
                    </FormCard>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" asChild>
                            <Link href={BASE}>Kembali</Link>
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            {isEdit
                                ? 'Simpan perubahan'
                                : 'Simpan & lanjut ke orang tua'}
                        </Button>
                    </div>
                </form>
            )}

            {isEdit && activeTab === 'orang-tua' && (
                <StudentGuardiansPanel
                    student={student}
                    relationships={options.relationships}
                />
            )}

            {isEdit && activeTab === 'riwayat' && (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {enrollments.length === 0 ? (
                        <EmptyState
                            icon={History}
                            title="Belum pernah masuk rombel"
                            description="Tambahkan siswa ke rombel melalui menu Kelas."
                        />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {enrollments.map((enrollment, index) => (
                                <li
                                    key={index}
                                    className="flex items-center justify-between gap-3 px-5 py-3"
                                >
                                    <div>
                                        <p className="text-sm font-medium text-slate-900">
                                            {enrollment.classroom}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            TA {enrollment.academic_year}
                                        </p>
                                    </div>
                                    <StatusBadge
                                        variant={
                                            ENROLLMENT_VARIANT[
                                                enrollment.status.value
                                            ] ?? 'neutral'
                                        }
                                    >
                                        {enrollment.status.label}
                                    </StatusBadge>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            <ConfirmDialog
                open={confirmDelete}
                onOpenChange={setConfirmDelete}
                variant="danger"
                title={`Hapus data ${student?.name ?? 'siswa'}?`}
                description="Gunakan hapus hanya untuk salah input. Siswa yang pindah/keluar sebaiknya diubah statusnya agar riwayat tetap tersimpan."
                confirmLabel="Ya, hapus"
                processing={deleting}
                onConfirm={destroy}
            />
        </AppLayout>
    );
}
