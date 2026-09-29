import { Head, useForm } from '@inertiajs/react';
import { GalleryHorizontal, Plus } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { RowActions } from '@/Components/shared/RowActions';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { ImageField } from '@/Modules/Cms/Components/ImageField';
import { toLocalInput } from '@/Modules/Cms/Components/PublishCard';
import type { BannerRow } from '@/Modules/Cms/types';

type Props = { banners: BannerRow[] };

const BASE = '/operator/cms/banner';

function period(banner: BannerRow): string {
    if (!banner.starts_at && !banner.ends_at) {
        return 'Tanpa batas waktu';
    }

    const from = banner.starts_at ? formatDate(banner.starts_at) : '…';
    const to = banner.ends_at ? formatDate(banner.ends_at) : '…';

    return `${from} – ${to}`;
}

export default function CmsBanners({ banners }: Props) {
    const [editing, setEditing] = useState<{
        open: boolean;
        row: BannerRow | null;
    }>({ open: false, row: null });
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: 'Banner', href: BASE },
            ]}
        >
            <Head title="Banner beranda" />
            <PageHeader
                title="Banner beranda"
                description="Gambar besar di bagian atas beranda website. Tampil berurutan sesuai nomor urut dan periode tayang."
                actions={
                    <Button
                        onClick={() => setEditing({ open: true, row: null })}
                    >
                        <Plus />
                        Tambah banner
                    </Button>
                }
            />

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {banners.length === 0 ? (
                    <EmptyState
                        icon={GalleryHorizontal}
                        title="Belum ada banner"
                        description="Tanpa banner, beranda memakai tampilan bawaan dengan nama sekolah."
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {banners.map((banner) => (
                            <li
                                key={banner.public_id}
                                className="flex items-center gap-3 px-4 py-3 sm:px-5"
                            >
                                {banner.image && (
                                    <img
                                        src={banner.image.url}
                                        alt=""
                                        className="aspect-video w-20 shrink-0 rounded-lg object-cover sm:w-32"
                                    />
                                )}
                                <div className="min-w-0 flex-1 space-y-0.5">
                                    <p className="font-medium break-words text-slate-900">
                                        {banner.title}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        Urutan {banner.position} ·{' '}
                                        {period(banner)}
                                    </p>
                                    <StatusBadge
                                        variant={
                                            banner.is_live
                                                ? 'success'
                                                : 'neutral'
                                        }
                                        dot
                                    >
                                        {banner.is_live
                                            ? 'Tayang'
                                            : banner.is_active
                                              ? 'Di luar periode'
                                              : 'Nonaktif'}
                                    </StatusBadge>
                                </div>
                                <RowActions
                                    label={banner.title}
                                    onEdit={() =>
                                        setEditing({ open: true, row: banner })
                                    }
                                    onDelete={() =>
                                        confirmDelete({
                                            url: `${BASE}/${banner.public_id}`,
                                            title: `Hapus banner ${banner.title}?`,
                                        })
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <Dialog
                open={editing.open}
                onOpenChange={(open) => setEditing((c) => ({ ...c, open }))}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                    <BannerForm
                        key={editing.row?.public_id ?? 'new'}
                        row={editing.row}
                        nextPosition={banners.length + 1}
                        onClose={() => setEditing({ open: false, row: null })}
                    />
                </DialogContent>
            </Dialog>
            {dialog}
        </AppLayout>
    );
}

function BannerForm({
    row,
    nextPosition,
    onClose,
}: {
    row: BannerRow | null;
    nextPosition: number;
    onClose: () => void;
}) {
    const form = useForm({
        title: row?.title ?? '',
        subtitle: row?.subtitle ?? '',
        link_url: row?.link_url ?? '',
        link_label: row?.link_label ?? '',
        position: row?.position ?? nextPosition,
        is_active: row?.is_active ?? true,
        starts_at: toLocalInput(row?.starts_at ?? null),
        ends_at: toLocalInput(row?.ends_at ?? null),
        image: null as File | null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            ...(row ? { _method: 'put' } : {}),
        }));
        form.post(row ? `${BASE}/${row.public_id}` : BASE, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>
                    {row ? 'Ubah banner' : 'Tambah banner'}
                </DialogTitle>
            </DialogHeader>
            <ImageField
                id="banner-image"
                current={row?.image?.url ?? null}
                file={form.data.image}
                removed={false}
                onFileChange={(file) => form.setData('image', file)}
                error={form.errors.image}
                hint="Rasio lebar 16:9 atau lebih lebar · maks 5 MB."
            />
            <div className="grid gap-2">
                <Label htmlFor="banner-title">Judul</Label>
                <Input
                    id="banner-title"
                    maxLength={120}
                    value={form.data.title}
                    onChange={(e) => form.setData('title', e.target.value)}
                    aria-invalid={!!form.errors.title}
                />
                <InputError message={form.errors.title} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="banner-subtitle">Subjudul (opsional)</Label>
                <Input
                    id="banner-subtitle"
                    maxLength={250}
                    value={form.data.subtitle}
                    onChange={(e) => form.setData('subtitle', e.target.value)}
                />
                <InputError message={form.errors.subtitle} />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="banner-link">Tautan tombol</Label>
                    <Input
                        id="banner-link"
                        maxLength={255}
                        value={form.data.link_url}
                        onChange={(e) =>
                            form.setData('link_url', e.target.value)
                        }
                        placeholder="/ppdb"
                        aria-invalid={!!form.errors.link_url}
                    />
                    <InputError message={form.errors.link_url} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="banner-link-label">Teks tombol</Label>
                    <Input
                        id="banner-link-label"
                        maxLength={40}
                        value={form.data.link_label}
                        onChange={(e) =>
                            form.setData('link_label', e.target.value)
                        }
                        placeholder="Daftar sekarang"
                    />
                    <InputError message={form.errors.link_label} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="banner-starts">Mulai tayang</Label>
                    <Input
                        id="banner-starts"
                        type="datetime-local"
                        value={form.data.starts_at}
                        onChange={(e) =>
                            form.setData('starts_at', e.target.value)
                        }
                    />
                    <InputError message={form.errors.starts_at} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="banner-ends">Selesai tayang</Label>
                    <Input
                        id="banner-ends"
                        type="datetime-local"
                        value={form.data.ends_at}
                        onChange={(e) =>
                            form.setData('ends_at', e.target.value)
                        }
                        aria-invalid={!!form.errors.ends_at}
                    />
                    <InputError message={form.errors.ends_at} />
                </div>
            </div>
            <div className="flex flex-wrap items-end gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="banner-position">Urutan</Label>
                    <Input
                        id="banner-position"
                        type="number"
                        min={0}
                        max={999}
                        value={form.data.position}
                        onChange={(e) =>
                            form.setData('position', Number(e.target.value))
                        }
                        className="w-24"
                    />
                </div>
                <label className="flex items-center gap-2 pb-2 text-sm">
                    <Checkbox
                        checked={form.data.is_active}
                        onCheckedChange={(c) =>
                            form.setData('is_active', c === true)
                        }
                    />
                    Aktif
                </label>
            </div>
            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Simpan
                </Button>
            </DialogFooter>
        </form>
    );
}
