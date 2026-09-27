import { Head, useForm } from '@inertiajs/react';
import { ImageOff } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
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
import AppLayout from '@/Layouts/AppLayout';

type Props = {
    settings: {
        school_name: string;
        school_address: string | null;
        school_phone: string | null;
        school_email: string | null;
        timezone: string;
        upload_max_size_kb: number;
        logo_url: string | null;
    };
    timezones: { value: string; label: string }[];
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

export default function Settings({
    settings,
    timezones,
    uploadHardMaxKb,
}: Props) {
    const form = useForm({
        school_name: settings.school_name,
        school_address: settings.school_address ?? '',
        school_phone: settings.school_phone ?? '',
        school_email: settings.school_email ?? '',
        timezone: settings.timezone,
        upload_max_size_kb: String(settings.upload_max_size_kb),
        logo: null as File | null,
        remove_logo: false,
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
        settings.logo_url && !form.data.remove_logo && !form.data.logo;

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
                    description="Tampil di aplikasi, dokumen PDF, dan email."
                >
                    <div className="grid gap-2">
                        <Label htmlFor="school_name">Nama sekolah</Label>
                        <Input
                            id="school_name"
                            value={form.data.school_name}
                            onChange={(event) =>
                                form.setData('school_name', event.target.value)
                            }
                            aria-invalid={!!form.errors.school_name}
                        />
                        <InputError message={form.errors.school_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="school_address">Alamat</Label>
                        <textarea
                            id="school_address"
                            rows={3}
                            value={form.data.school_address}
                            onChange={(event) =>
                                form.setData(
                                    'school_address',
                                    event.target.value,
                                )
                            }
                            className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                        <InputError message={form.errors.school_address} />
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="school_phone">Telepon</Label>
                            <Input
                                id="school_phone"
                                value={form.data.school_phone}
                                onChange={(event) =>
                                    form.setData(
                                        'school_phone',
                                        event.target.value,
                                    )
                                }
                                placeholder="(0717) 123456"
                                aria-invalid={!!form.errors.school_phone}
                            />
                            <InputError message={form.errors.school_phone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="school_email">Email sekolah</Label>
                            <Input
                                id="school_email"
                                type="email"
                                value={form.data.school_email}
                                onChange={(event) =>
                                    form.setData(
                                        'school_email',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.school_email}
                            />
                            <InputError message={form.errors.school_email} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label>Logo sekolah</Label>
                        {showCurrentLogo ? (
                            <div className="flex items-center gap-4 rounded-xl border border-slate-200 p-3">
                                <img
                                    src={settings.logo_url ?? undefined}
                                    alt="Logo sekolah"
                                    className="size-16 rounded-lg bg-slate-50 object-contain"
                                />
                                <div className="flex flex-wrap gap-2">
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
                    title="Sistem"
                    description="Zona waktu dan batas ukuran unggahan file."
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="timezone">Zona waktu</Label>
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
                            <p className="text-xs text-slate-500">
                                Sebaiknya hanya diubah saat awal instalasi.
                            </p>
                            <InputError message={form.errors.timezone} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="upload_max_size_kb">
                                Ukuran maksimum upload (KB)
                            </Label>
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
                            <p className="text-xs text-slate-500">
                                Maksimum{' '}
                                {uploadHardMaxKb.toLocaleString('id-ID')} KB
                                (batas server).
                            </p>
                            <InputError
                                message={form.errors.upload_max_size_kb}
                            />
                        </div>
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
