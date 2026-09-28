import { Head, useForm } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
import { SignatureField } from '@/Components/shared/SignatureField';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';

type Option = { value: string; label: string };

type Props = {
    school: {
        name: string;
        npsn: string | null;
        level: string;
        address: string | null;
        phone: string | null;
        email: string | null;
        principal_name: string | null;
        principal_nip: string | null;
        logo_url: string | null;
        principal_signature_url: string | null;
    };
    system: {
        timezone: string;
        upload_max_size_kb: number;
        attendance_mode: string;
        attendance_edit_days: number;
        teacher_late_after: string;
        teacher_attendance_validation: string;
        teacher_attendance_ips: string;
        school_latitude: number | null;
        school_longitude: number | null;
        teacher_attendance_radius_m: number;
        grade_kkm: number;
        grade_predicate_a: number;
        grade_predicate_b: number;
        grade_predicate_c: number;
    };
    attendanceModes: Option[];
    teacherValidations: Option[];
    levels: Option[];
    timezones: Option[];
    uploadHardMaxKb: number;
};

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: ReactNode;
}) {
    return (
        <section className="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-3">
            <div>
                <h2 className="text-base font-semibold text-slate-900">
                    {title}
                </h2>
                <p className="mt-1 text-sm text-slate-500">{description}</p>
            </div>
            <div className="space-y-5 lg:col-span-2">{children}</div>
        </section>
    );
}

function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export default function Settings({
    school,
    system,
    levels,
    timezones,
    uploadHardMaxKb,
    attendanceModes,
    teacherValidations,
}: Props) {
    const form = useForm({
        name: school.name,
        npsn: school.npsn ?? '',
        level: school.level,
        address: school.address ?? '',
        phone: school.phone ?? '',
        email: school.email ?? '',
        principal_name: school.principal_name ?? '',
        principal_nip: school.principal_nip ?? '',
        logo: null as File | null,
        remove_logo: false,
        timezone: system.timezone,
        upload_max_size_kb: String(system.upload_max_size_kb),
        attendance_mode: system.attendance_mode,
        attendance_edit_days: String(system.attendance_edit_days),
        teacher_late_after: system.teacher_late_after,
        teacher_attendance_validation: system.teacher_attendance_validation,
        teacher_attendance_ips: system.teacher_attendance_ips,
        school_latitude: system.school_latitude?.toString() ?? '',
        school_longitude: system.school_longitude?.toString() ?? '',
        teacher_attendance_radius_m: String(system.teacher_attendance_radius_m),
        grade_kkm: String(system.grade_kkm),
        grade_predicate_a: String(system.grade_predicate_a),
        grade_predicate_b: String(system.grade_predicate_b),
        grade_predicate_c: String(system.grade_predicate_c),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/operator/settings', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({
                    ...data,
                    logo: null,
                    remove_logo: false,
                })),
        });
    };

    const showCurrentLogo =
        school.logo_url && !form.data.remove_logo && !form.data.logo;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pengaturan Sekolah', href: '/operator/settings' },
            ]}
        >
            <Head title="Pengaturan Sekolah" />

            <PageHeader
                title="Pengaturan Sekolah"
                description="Identitas sekolah dan pengaturan sistem yang dipakai seluruh aplikasi."
            />

            <form onSubmit={submit} className="space-y-6">
                <Section
                    title="Identitas sekolah"
                    description="Tampil di aplikasi, kop dokumen PDF, dan email."
                >
                    <Field
                        id="name"
                        label="Nama sekolah"
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
                    </Field>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="npsn"
                            label="NPSN"
                            error={form.errors.npsn}
                            hint="8 digit, opsional"
                        >
                            <Input
                                id="npsn"
                                inputMode="numeric"
                                maxLength={8}
                                value={form.data.npsn}
                                onChange={(event) =>
                                    form.setData('npsn', event.target.value)
                                }
                                aria-invalid={!!form.errors.npsn}
                            />
                        </Field>
                        <Field
                            id="level"
                            label="Jenjang"
                            error={form.errors.level}
                            hint="Menentukan tingkat kelas yang tersedia."
                        >
                            <Select
                                value={form.data.level}
                                onValueChange={(value) =>
                                    form.setData('level', value)
                                }
                            >
                                <SelectTrigger id="level">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {levels.map((level) => (
                                        <SelectItem
                                            key={level.value}
                                            value={level.value}
                                        >
                                            {level.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    </div>

                    <Field
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
                    </Field>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="phone"
                            label="Telepon"
                            error={form.errors.phone}
                        >
                            <Input
                                id="phone"
                                value={form.data.phone}
                                onChange={(event) =>
                                    form.setData('phone', event.target.value)
                                }
                                placeholder="(0717) 123456"
                                aria-invalid={!!form.errors.phone}
                            />
                        </Field>
                        <Field
                            id="email"
                            label="Email sekolah"
                            error={form.errors.email}
                        >
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) =>
                                    form.setData('email', event.target.value)
                                }
                                aria-invalid={!!form.errors.email}
                            />
                        </Field>
                    </div>

                    <div className="grid gap-2">
                        <Label>Logo sekolah</Label>
                        {showCurrentLogo ? (
                            <div className="flex items-center gap-4 rounded-xl border border-slate-200 p-3">
                                <img
                                    src={school.logo_url ?? undefined}
                                    alt="Logo sekolah"
                                    className="size-16 rounded-lg bg-slate-50 object-contain"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        form.setData('remove_logo', true)
                                    }
                                >
                                    <ImageOff />
                                    Hapus logo
                                </Button>
                            </div>
                        ) : (
                            <FileUploader
                                value={form.data.logo ? [form.data.logo] : []}
                                onChange={(files) =>
                                    form.setData('logo', files[0] ?? null)
                                }
                                accept={[
                                    'image/jpeg',
                                    'image/png',
                                    'image/webp',
                                ]}
                                maxSizeKb={2048}
                                hint="JPG, PNG, atau WebP · maks 2 MB · disarankan persegi"
                                error={form.errors.logo}
                            />
                        )}
                        {form.data.remove_logo && (
                            <p className="text-xs text-amber-700">
                                Logo akan dihapus saat pengaturan disimpan.{' '}
                                <button
                                    type="button"
                                    className="font-medium underline"
                                    onClick={() =>
                                        form.setData('remove_logo', false)
                                    }
                                >
                                    Batalkan
                                </button>
                            </p>
                        )}
                    </div>
                </Section>

                <Section
                    title="Kepala sekolah"
                    description="Dipakai untuk tanda tangan raport dan dokumen resmi."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="principal_name"
                            label="Nama kepala sekolah"
                            error={form.errors.principal_name}
                        >
                            <Input
                                id="principal_name"
                                value={form.data.principal_name}
                                onChange={(event) =>
                                    form.setData(
                                        'principal_name',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.principal_name}
                            />
                        </Field>
                        <Field
                            id="principal_nip"
                            label="NIP"
                            error={form.errors.principal_nip}
                        >
                            <Input
                                id="principal_nip"
                                inputMode="numeric"
                                value={form.data.principal_nip}
                                onChange={(event) =>
                                    form.setData(
                                        'principal_nip',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.principal_nip}
                            />
                        </Field>
                    </div>
                    <div className="grid gap-2">
                        <Label>Tanda tangan (PDF raport)</Label>
                        <SignatureField
                            url={school.principal_signature_url}
                            endpoint="/operator/settings/tanda-tangan"
                        />
                    </div>
                </Section>

                <Section
                    title="Sistem"
                    description="Zona waktu dan batas ukuran unggahan file."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="timezone"
                            label="Zona waktu"
                            error={form.errors.timezone}
                            hint="Sebaiknya hanya diubah saat awal instalasi."
                        >
                            <Select
                                value={form.data.timezone}
                                onValueChange={(value) =>
                                    form.setData('timezone', value)
                                }
                            >
                                <SelectTrigger id="timezone">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {timezones.map((timezone) => (
                                        <SelectItem
                                            key={timezone.value}
                                            value={timezone.value}
                                        >
                                            {timezone.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field
                            id="upload_max_size_kb"
                            label="Ukuran maksimum upload (KB)"
                            error={form.errors.upload_max_size_kb}
                            hint={`Maksimum ${uploadHardMaxKb.toLocaleString('id-ID')} KB (batas server).`}
                        >
                            <Input
                                id="upload_max_size_kb"
                                type="number"
                                min={256}
                                max={uploadHardMaxKb}
                                step={256}
                                value={form.data.upload_max_size_kb}
                                onChange={(event) =>
                                    form.setData(
                                        'upload_max_size_kb',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.upload_max_size_kb}
                            />
                        </Field>
                    </div>
                </Section>

                <Section
                    title="Presensi"
                    description="Cara guru mengisi presensi siswa dan berapa lama presensi masih bisa diubah guru."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="attendance_mode"
                            label="Mode presensi"
                            error={form.errors.attendance_mode}
                            hint="Per mata pelajaran memerlukan jadwal pelajaran yang sudah disusun."
                        >
                            <Select
                                value={form.data.attendance_mode}
                                onValueChange={(value) =>
                                    form.setData('attendance_mode', value)
                                }
                            >
                                <SelectTrigger id="attendance_mode">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {attendanceModes.map((mode) => (
                                        <SelectItem
                                            key={mode.value}
                                            value={mode.value}
                                        >
                                            {mode.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field
                            id="attendance_edit_days"
                            label="Batas edit guru (hari)"
                            error={form.errors.attendance_edit_days}
                            hint="0 = hanya di hari yang sama. Setelah batas, hanya operator yang bisa mengoreksi."
                        >
                            <Input
                                id="attendance_edit_days"
                                type="number"
                                min={0}
                                max={30}
                                value={form.data.attendance_edit_days}
                                onChange={(event) =>
                                    form.setData(
                                        'attendance_edit_days',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={
                                    !!form.errors.attendance_edit_days
                                }
                            />
                        </Field>
                    </div>
                </Section>

                <Section
                    title="Presensi guru"
                    description="Jam batas terlambat dan validasi lokasi saat guru presensi masuk dari dashboard."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <Field
                            id="teacher_late_after"
                            label="Terlambat setelah jam"
                            error={form.errors.teacher_late_after}
                        >
                            <Input
                                id="teacher_late_after"
                                type="time"
                                value={form.data.teacher_late_after}
                                onChange={(event) =>
                                    form.setData(
                                        'teacher_late_after',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.teacher_late_after}
                            />
                        </Field>
                        <Field
                            id="teacher_attendance_validation"
                            label="Validasi lokasi"
                            error={form.errors.teacher_attendance_validation}
                        >
                            <Select
                                value={form.data.teacher_attendance_validation}
                                onValueChange={(value) =>
                                    form.setData(
                                        'teacher_attendance_validation',
                                        value,
                                    )
                                }
                            >
                                <SelectTrigger id="teacher_attendance_validation">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {teacherValidations.map((option) => (
                                        <SelectItem
                                            key={option.value}
                                            value={option.value}
                                        >
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    </div>

                    {form.data.teacher_attendance_validation === 'IP' && (
                        <Field
                            id="teacher_attendance_ips"
                            label="Daftar IP jaringan sekolah"
                            error={form.errors.teacher_attendance_ips}
                            hint="Satu per baris atau dipisah koma. Mendukung CIDR, mis. 103.10.20.0/24. Gunakan IP publik yang terlihat oleh server."
                        >
                            <Textarea
                                id="teacher_attendance_ips"
                                rows={3}
                                value={form.data.teacher_attendance_ips}
                                onChange={(event) =>
                                    form.setData(
                                        'teacher_attendance_ips',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={
                                    !!form.errors.teacher_attendance_ips
                                }
                            />
                        </Field>
                    )}

                    {form.data.teacher_attendance_validation === 'GPS' && (
                        <div className="grid gap-5 sm:grid-cols-3">
                            <Field
                                id="school_latitude"
                                label="Lintang (latitude)"
                                error={form.errors.school_latitude}
                            >
                                <Input
                                    id="school_latitude"
                                    inputMode="decimal"
                                    placeholder="-2.1291"
                                    value={form.data.school_latitude}
                                    onChange={(event) =>
                                        form.setData(
                                            'school_latitude',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!form.errors.school_latitude}
                                />
                            </Field>
                            <Field
                                id="school_longitude"
                                label="Bujur (longitude)"
                                error={form.errors.school_longitude}
                            >
                                <Input
                                    id="school_longitude"
                                    inputMode="decimal"
                                    placeholder="106.1134"
                                    value={form.data.school_longitude}
                                    onChange={(event) =>
                                        form.setData(
                                            'school_longitude',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={
                                        !!form.errors.school_longitude
                                    }
                                />
                            </Field>
                            <Field
                                id="teacher_attendance_radius_m"
                                label="Radius (meter)"
                                error={form.errors.teacher_attendance_radius_m}
                            >
                                <Input
                                    id="teacher_attendance_radius_m"
                                    type="number"
                                    min={10}
                                    max={5000}
                                    value={
                                        form.data.teacher_attendance_radius_m
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'teacher_attendance_radius_m',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={
                                        !!form.errors
                                            .teacher_attendance_radius_m
                                    }
                                />
                            </Field>
                            <p className="text-xs text-slate-500 sm:col-span-3">
                                Koordinat dari browser bisa dipalsukan; validasi
                                GPS hanya pencegah ringan. Koordinat dan jarak
                                dicatat untuk audit.
                            </p>
                        </div>
                    )}
                </Section>

                <Section
                    title="Penilaian"
                    description="KKM dan batas nilai akhir untuk predikat. Nilai di bawah batas C mendapat predikat D. Berlaku untuk semua mata pelajaran."
                >
                    <div className="grid gap-5 sm:grid-cols-4">
                        <Field
                            id="grade_kkm"
                            label="KKM"
                            error={form.errors.grade_kkm}
                        >
                            <Input
                                id="grade_kkm"
                                type="number"
                                min={0}
                                max={100}
                                value={form.data.grade_kkm}
                                onChange={(event) =>
                                    form.setData(
                                        'grade_kkm',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.grade_kkm}
                            />
                        </Field>
                        <Field
                            id="grade_predicate_a"
                            label="Predikat A mulai"
                            error={form.errors.grade_predicate_a}
                        >
                            <Input
                                id="grade_predicate_a"
                                type="number"
                                min={0}
                                max={100}
                                value={form.data.grade_predicate_a}
                                onChange={(event) =>
                                    form.setData(
                                        'grade_predicate_a',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.grade_predicate_a}
                            />
                        </Field>
                        <Field
                            id="grade_predicate_b"
                            label="Predikat B mulai"
                            error={form.errors.grade_predicate_b}
                        >
                            <Input
                                id="grade_predicate_b"
                                type="number"
                                min={0}
                                max={100}
                                value={form.data.grade_predicate_b}
                                onChange={(event) =>
                                    form.setData(
                                        'grade_predicate_b',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.grade_predicate_b}
                            />
                        </Field>
                        <Field
                            id="grade_predicate_c"
                            label="Predikat C mulai"
                            error={form.errors.grade_predicate_c}
                        >
                            <Input
                                id="grade_predicate_c"
                                type="number"
                                min={0}
                                max={100}
                                value={form.data.grade_predicate_c}
                                onChange={(event) =>
                                    form.setData(
                                        'grade_predicate_c',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.grade_predicate_c}
                            />
                        </Field>
                    </div>
                </Section>

                <div className="flex justify-end">
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan pengaturan
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
