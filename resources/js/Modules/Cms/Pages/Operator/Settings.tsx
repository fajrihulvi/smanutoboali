import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { FormCard } from '@/Modules/Cms/Components/FormCard';

type SettingKey =
    | 'tagline'
    | 'about'
    | 'address'
    | 'phone'
    | 'whatsapp'
    | 'email'
    | 'opening_hours'
    | 'map_embed_url'
    | 'facebook'
    | 'instagram'
    | 'youtube'
    | 'tiktok'
    | 'ppdb_cta_title'
    | 'ppdb_cta_text'
    | 'footer_text';

type Props = { settings: Record<SettingKey, string> };

type FieldDef = {
    key: SettingKey;
    label: string;
    placeholder?: string;
    multiline?: boolean;
    max: number;
    type?: string;
};

const GROUPS: { title: string; fields: FieldDef[] }[] = [
    {
        title: 'Identitas',
        fields: [
            {
                key: 'tagline',
                label: 'Slogan',
                placeholder: 'Cerdas, berkarakter, dan berprestasi',
                max: 150,
            },
            {
                key: 'about',
                label: 'Deskripsi singkat',
                multiline: true,
                max: 500,
            },
        ],
    },
    {
        title: 'Kontak',
        fields: [
            { key: 'address', label: 'Alamat', multiline: true, max: 300 },
            { key: 'phone', label: 'Telepon', max: 30, type: 'tel' },
            {
                key: 'whatsapp',
                label: 'WhatsApp',
                placeholder: '6281234567890',
                max: 20,
                type: 'tel',
            },
            { key: 'email', label: 'Email', max: 120, type: 'email' },
            {
                key: 'opening_hours',
                label: 'Jam layanan',
                placeholder: 'Senin–Jumat, 07.00–15.00',
                max: 150,
            },
            {
                key: 'map_embed_url',
                label: 'Tautan peta (Google Maps → Bagikan → Sematkan peta)',
                placeholder: 'https://www.google.com/maps/embed?pb=…',
                max: 1000,
            },
        ],
    },
    {
        title: 'Media sosial',
        fields: [
            {
                key: 'facebook',
                label: 'Facebook',
                placeholder: 'https://facebook.com/…',
                max: 255,
            },
            {
                key: 'instagram',
                label: 'Instagram',
                placeholder: 'https://instagram.com/…',
                max: 255,
            },
            {
                key: 'youtube',
                label: 'YouTube',
                placeholder: 'https://youtube.com/@…',
                max: 255,
            },
            {
                key: 'tiktok',
                label: 'TikTok',
                placeholder: 'https://tiktok.com/@…',
                max: 255,
            },
        ],
    },
    {
        title: 'Ajakan PPDB & footer',
        fields: [
            { key: 'ppdb_cta_title', label: 'Judul ajakan PPDB', max: 100 },
            {
                key: 'ppdb_cta_text',
                label: 'Teks ajakan PPDB',
                multiline: true,
                max: 300,
            },
            {
                key: 'footer_text',
                label: 'Teks footer',
                multiline: true,
                max: 300,
            },
        ],
    },
];

export default function CmsSettings({ settings }: Props) {
    const form = useForm<Record<SettingKey, string>>({ ...settings });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put('/operator/cms/pengaturan', { preserveScroll: true });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: 'Pengaturan', href: '/operator/cms/pengaturan' },
            ]}
        >
            <Head title="Pengaturan website" />
            <PageHeader
                title="Pengaturan website"
                description="Nama dan logo sekolah mengikuti Pengaturan Sekolah. Kolom kosong tidak ditampilkan di website."
            />
            <form onSubmit={submit} className="max-w-3xl space-y-6">
                {GROUPS.map((group) => (
                    <FormCard key={group.title} title={group.title}>
                        <div className="grid gap-4 sm:grid-cols-2">
                            {group.fields.map((field) => {
                                const id = `setting-${field.key}`;
                                const wide =
                                    field.multiline ||
                                    field.key === 'map_embed_url';

                                return (
                                    <div
                                        key={field.key}
                                        className={
                                            wide
                                                ? 'grid min-w-0 gap-2 sm:col-span-2'
                                                : 'grid min-w-0 gap-2'
                                        }
                                    >
                                        <Label htmlFor={id}>
                                            {field.label}
                                        </Label>
                                        {field.multiline ? (
                                            <Textarea
                                                id={id}
                                                rows={3}
                                                maxLength={field.max}
                                                value={form.data[field.key]}
                                                onChange={(e) =>
                                                    form.setData(
                                                        field.key,
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder={field.placeholder}
                                                aria-invalid={
                                                    !!form.errors[field.key]
                                                }
                                            />
                                        ) : (
                                            <Input
                                                id={id}
                                                type={field.type ?? 'text'}
                                                maxLength={field.max}
                                                value={form.data[field.key]}
                                                onChange={(e) =>
                                                    form.setData(
                                                        field.key,
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder={field.placeholder}
                                                aria-invalid={
                                                    !!form.errors[field.key]
                                                }
                                            />
                                        )}
                                        <InputError
                                            message={form.errors[field.key]}
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    </FormCard>
                ))}
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Simpan pengaturan
                </Button>
            </form>
        </AppLayout>
    );
}
