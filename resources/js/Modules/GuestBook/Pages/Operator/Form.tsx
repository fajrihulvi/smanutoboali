import { Head, Link, useForm } from '@inertiajs/react';
import { format } from 'date-fns';
import type { FormEvent } from 'react';
import { PageHeader } from '@/Components/shared/PageHeader';
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
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import type { GuestVisitRow, Option } from '@/Modules/GuestBook/types';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import { PhotoField } from '@/Modules/People/Components/PhotoField';

type Props = {
    visit: GuestVisitRow | null;
    categories: Option[];
    hosts: Option[];
};

const BASE = '/operator/buku-tamu';

/** Nilai input datetime-local (waktu lokal perangkat). */
function localDateTime(value: string | Date): string {
    return format(new Date(value), "yyyy-MM-dd'T'HH:mm");
}

function OptionSelect({
    id,
    value,
    options,
    invalid,
    onChange,
}: {
    id: string;
    value: string;
    options: Option[];
    invalid: boolean;
    onChange: (value: string) => void;
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger id={id} aria-invalid={invalid}>
                <SelectValue placeholder="Pilih" />
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

export default function GuestVisitForm({ visit, categories, hosts }: Props) {
    const isEdit = visit !== null;

    const form = useForm({
        full_name: visit?.full_name ?? '',
        institution: visit?.institution ?? '',
        whatsapp: visit?.whatsapp ?? '',
        category: visit?.category.value ?? '',
        host: visit?.host.value ?? '',
        purpose: visit?.purpose ?? '',
        notes: visit?.notes ?? '',
        visited_at: localDateTime(visit?.visited_at ?? new Date()),
        photo: null as File | null,
        remove_photo: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(isEdit ? `${BASE}/${visit.public_id}` : BASE, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const title = isEdit ? visit.full_name : 'Catat tamu';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Buku Tamu', href: BASE },
                {
                    title: isEdit ? 'Ubah' : 'Catat',
                    href: isEdit
                        ? `${BASE}/${visit.public_id}/ubah`
                        : `${BASE}/tambah`,
                },
            ]}
        >
            <Head title={title} />

            <PageHeader
                title={title}
                description={
                    isEdit
                        ? 'Perbarui data kunjungan tamu.'
                        : 'Catat tamu yang datang tanpa mengisi form di website.'
                }
            />

            <form onSubmit={submit} className="grid max-w-3xl gap-6">
                <FormCard title="Data tamu">
                    <FormField
                        id="full_name"
                        label="Nama lengkap"
                        error={form.errors.full_name}
                    >
                        <Input
                            id="full_name"
                            value={form.data.full_name}
                            maxLength={150}
                            onChange={(e) =>
                                form.setData('full_name', e.target.value)
                            }
                            aria-invalid={!!form.errors.full_name}
                        />
                    </FormField>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            id="institution"
                            label="Instansi asal"
                            error={form.errors.institution}
                        >
                            <Input
                                id="institution"
                                value={form.data.institution}
                                maxLength={150}
                                onChange={(e) =>
                                    form.setData('institution', e.target.value)
                                }
                                aria-invalid={!!form.errors.institution}
                            />
                        </FormField>
                        <FormField
                            id="whatsapp"
                            label="Nomor WhatsApp"
                            error={form.errors.whatsapp}
                        >
                            <Input
                                id="whatsapp"
                                inputMode="tel"
                                placeholder="08xxxxxxxxxx"
                                maxLength={20}
                                value={form.data.whatsapp}
                                onChange={(e) =>
                                    form.setData('whatsapp', e.target.value)
                                }
                                aria-invalid={!!form.errors.whatsapp}
                            />
                        </FormField>
                        <FormField
                            id="category"
                            label="Kategori tamu"
                            error={form.errors.category}
                        >
                            <OptionSelect
                                id="category"
                                value={form.data.category}
                                options={categories}
                                invalid={!!form.errors.category}
                                onChange={(value) =>
                                    form.setData('category', value)
                                }
                            />
                        </FormField>
                        <FormField
                            id="host"
                            label="Bertemu dengan"
                            error={form.errors.host}
                        >
                            <OptionSelect
                                id="host"
                                value={form.data.host}
                                options={hosts}
                                invalid={!!form.errors.host}
                                onChange={(value) =>
                                    form.setData('host', value)
                                }
                            />
                        </FormField>
                    </div>
                    <FormField
                        id="purpose"
                        label="Keperluan"
                        error={form.errors.purpose}
                    >
                        <Textarea
                            id="purpose"
                            rows={3}
                            maxLength={1000}
                            value={form.data.purpose}
                            onChange={(e) =>
                                form.setData('purpose', e.target.value)
                            }
                            aria-invalid={!!form.errors.purpose}
                        />
                    </FormField>
                    <div className="grid gap-5 sm:grid-cols-2">
                        <FormField
                            id="visited_at"
                            label="Waktu kunjungan"
                            error={form.errors.visited_at}
                        >
                            <Input
                                id="visited_at"
                                type="datetime-local"
                                value={form.data.visited_at}
                                max={localDateTime(new Date())}
                                onChange={(e) =>
                                    form.setData('visited_at', e.target.value)
                                }
                                aria-invalid={!!form.errors.visited_at}
                            />
                        </FormField>
                    </div>
                    <FormField
                        id="photo"
                        label="Foto tamu (opsional)"
                        error={form.errors.photo}
                    >
                        <PhotoField
                            name={form.data.full_name || 'Tamu'}
                            currentUrl={visit?.photo_thumb_url}
                            file={form.data.photo}
                            remove={form.data.remove_photo}
                            onFileChange={(file) => form.setData('photo', file)}
                            onRemoveChange={(remove) =>
                                form.setData('remove_photo', remove)
                            }
                            error={form.errors.photo}
                        />
                    </FormField>
                    <FormField
                        id="notes"
                        label="Catatan (opsional)"
                        error={form.errors.notes}
                    >
                        <Textarea
                            id="notes"
                            rows={2}
                            maxLength={1000}
                            value={form.data.notes}
                            onChange={(e) =>
                                form.setData('notes', e.target.value)
                            }
                            aria-invalid={!!form.errors.notes}
                        />
                    </FormField>
                </FormCard>

                <div className="flex flex-wrap justify-end gap-2">
                    <Button variant="outline" asChild>
                        <Link href={BASE}>Batal</Link>
                    </Button>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
