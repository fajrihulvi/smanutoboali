import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Trash2, Undo2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import type { Option } from '@/Modules/Academic/types';
import { FormCard } from '@/Modules/Cms/Components/FormCard';
import {
    PublishCard,
    toLocalInput,
} from '@/Modules/Cms/Components/PublishCard';
import type { GalleryRow } from '@/Modules/Cms/types';

type Props = {
    gallery: GalleryRow | null;
    statuses: Option[];
    maxPhotos: number;
};

type PhotoItem = { id: string; caption: string; url: string | null };

const BASE = '/operator/cms/galeri';
const ACCEPT = ['image/jpeg', 'image/png', 'image/webp'];

export default function CmsGalleryForm({
    gallery,
    statuses,
    maxPhotos,
}: Props) {
    const form = useForm({
        title: gallery?.title ?? '',
        slug: gallery?.slug ?? '',
        description: gallery?.description ?? '',
        date: gallery?.date ?? '',
        status: gallery?.status.value ?? 'DRAFT',
        publish_at: toLocalInput(gallery?.publish_at ?? null),
        photos: (gallery?.photos ?? []).map((p): PhotoItem => ({
            id: p.public_id,
            caption: p.caption ?? '',
            url: p.thumb_url ?? p.url,
        })),
        remove_photos: [] as string[],
        uploads: [] as File[],
    });

    const kept = form.data.photos.filter(
        (p) => !form.data.remove_photos.includes(p.id),
    ).length;
    const room = Math.max(0, maxPhotos - kept);
    const error = (key: string) =>
        (form.errors as Record<string, string | undefined>)[key];

    const move = (index: number, delta: number) => {
        const next = [...form.data.photos];
        const target = index + delta;

        if (target < 0 || target >= next.length) {
            return;
        }

        [next[index], next[target]] = [next[target], next[index]];
        form.setData('photos', next);
    };

    const setCaption = (index: number, caption: string) =>
        form.setData(
            'photos',
            form.data.photos.map((p, i) =>
                i === index ? { ...p, caption } : p,
            ),
        );

    const toggleRemove = (id: string) =>
        form.setData(
            'remove_photos',
            form.data.remove_photos.includes(id)
                ? form.data.remove_photos.filter((r) => r !== id)
                : [...form.data.remove_photos, id],
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            photos: data.photos.map(({ id, caption }) => ({ id, caption })),
            ...(gallery ? { _method: 'put' } : {}),
        }));
        form.post(gallery ? `${BASE}/${gallery.public_id}` : BASE, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const fresh = (page.props as unknown as Props).gallery;
                form.setDefaults();
                form.setData((data) => ({
                    ...data,
                    uploads: [],
                    remove_photos: [],
                    photos: (fresh?.photos ?? []).map((p) => ({
                        id: p.public_id,
                        caption: p.caption ?? '',
                        url: p.thumb_url ?? p.url,
                    })),
                }));
            },
        });
    };

    const heading = gallery ? `Ubah album` : 'Buat album';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: 'Galeri', href: BASE },
                { title: gallery ? 'Ubah' : 'Buat', href: '#' },
            ]}
        >
            <Head title={heading} />
            <PageHeader title={heading} />

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                <div className="min-w-0 space-y-6 lg:col-span-2">
                    <FormCard title="Album">
                        <div className="grid gap-2">
                            <Label htmlFor="gallery-title">Judul</Label>
                            <Input
                                id="gallery-title"
                                maxLength={150}
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                placeholder="Peringatan HUT RI ke-81"
                                aria-invalid={!!form.errors.title}
                            />
                            <InputError message={form.errors.title} />
                        </div>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="gallery-date">
                                    Tanggal kegiatan
                                </Label>
                                <Input
                                    id="gallery-date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) =>
                                        form.setData('date', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.date} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="gallery-slug">Slug</Label>
                                <Input
                                    id="gallery-slug"
                                    maxLength={160}
                                    value={form.data.slug}
                                    onChange={(e) =>
                                        form.setData(
                                            'slug',
                                            e.target.value.toLowerCase(),
                                        )
                                    }
                                    placeholder="otomatis-dari-judul"
                                    aria-invalid={!!form.errors.slug}
                                />
                                <InputError message={form.errors.slug} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="gallery-description">
                                Deskripsi (opsional)
                            </Label>
                            <Textarea
                                id="gallery-description"
                                rows={3}
                                maxLength={1000}
                                value={form.data.description}
                                onChange={(e) =>
                                    form.setData('description', e.target.value)
                                }
                            />
                            <InputError message={form.errors.description} />
                        </div>
                    </FormCard>

                    <FormCard title={`Foto (${kept}/${maxPhotos})`}>
                        {form.data.photos.length > 0 && (
                            <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                {form.data.photos.map((photo, index) => {
                                    const removed =
                                        form.data.remove_photos.includes(
                                            photo.id,
                                        );

                                    return (
                                        <li
                                            key={photo.id}
                                            className={cn(
                                                'min-w-0 space-y-2 rounded-xl border border-slate-200 p-2',
                                                removed && 'opacity-50',
                                            )}
                                        >
                                            {photo.url && (
                                                <img
                                                    src={photo.url}
                                                    alt={photo.caption}
                                                    loading="lazy"
                                                    className="aspect-[4/3] w-full rounded-lg object-cover"
                                                />
                                            )}
                                            <Input
                                                value={photo.caption}
                                                maxLength={200}
                                                disabled={removed}
                                                onChange={(e) =>
                                                    setCaption(
                                                        index,
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Keterangan"
                                                aria-label={`Keterangan foto ${index + 1}`}
                                            />
                                            <div className="flex items-center justify-between">
                                                <div className="flex">
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        disabled={index === 0}
                                                        onClick={() =>
                                                            move(index, -1)
                                                        }
                                                        aria-label={`Geser foto ${index + 1} ke kiri`}
                                                    >
                                                        <ArrowLeft />
                                                    </Button>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8"
                                                        disabled={
                                                            index ===
                                                            form.data.photos
                                                                .length -
                                                                1
                                                        }
                                                        onClick={() =>
                                                            move(index, 1)
                                                        }
                                                        aria-label={`Geser foto ${index + 1} ke kanan`}
                                                    >
                                                        <ArrowRight />
                                                    </Button>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        toggleRemove(photo.id)
                                                    }
                                                >
                                                    {removed ? (
                                                        <Undo2 />
                                                    ) : (
                                                        <Trash2 />
                                                    )}
                                                    {removed
                                                        ? 'Batal'
                                                        : 'Hapus'}
                                                </Button>
                                            </div>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                        {room > 0 ? (
                            <FileUploader
                                value={form.data.uploads}
                                onChange={(files) =>
                                    form.setData(
                                        'uploads',
                                        files.slice(0, room),
                                    )
                                }
                                accept={ACCEPT}
                                multiple
                                maxFiles={room}
                                maxSizeKb={5120}
                                hint={`Tambah hingga ${room} foto · JPG/PNG/WebP · maks 5 MB per foto`}
                                error={error('uploads') ?? error('uploads.0')}
                            />
                        ) : (
                            <p className="text-xs text-slate-500">
                                Batas {maxPhotos} foto per album tercapai.
                            </p>
                        )}
                        <InputError message={error('photos.0.caption')} />
                    </FormCard>
                </div>

                <aside className="min-w-0 space-y-6">
                    <PublishCard
                        statuses={statuses}
                        current={gallery?.status ?? null}
                        status={form.data.status}
                        publishAt={form.data.publish_at}
                        onStatusChange={(v) =>
                            form.setData('status', v as typeof form.data.status)
                        }
                        onPublishAtChange={(v) => form.setData('publish_at', v)}
                        errors={form.errors}
                        processing={form.processing}
                        previewUrl={
                            gallery
                                ? `${BASE}/${gallery.public_id}/pratinjau`
                                : undefined
                        }
                    />
                </aside>
            </form>
        </AppLayout>
    );
}
