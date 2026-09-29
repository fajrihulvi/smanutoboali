import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { FormCard } from '@/Modules/Cms/Components/FormCard';
import { ImageField } from '@/Modules/Cms/Components/ImageField';
import {
    PublishCard,
    toLocalInput,
} from '@/Modules/Cms/Components/PublishCard';
import { RichTextEditor } from '@/Modules/Cms/Components/RichTextEditor';
import { SeoFields } from '@/Modules/Cms/Components/SeoFields';
import type { PageRow } from '@/Modules/Cms/types';

type Props = { page: PageRow | null; statuses: Option[] };

const BASE = '/operator/cms/halaman';

export default function CmsPageForm({ page, statuses }: Props) {
    const form = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        content: page?.content ?? '',
        excerpt: page?.excerpt ?? '',
        seo_title: page?.seo_title ?? '',
        seo_description: page?.seo_description ?? '',
        show_in_menu: page?.show_in_menu ?? true,
        position: page?.position ?? 0,
        status: page?.status.value ?? 'DRAFT',
        publish_at: toLocalInput(page?.publish_at ?? null),
        image: null as File | null,
        remove_image: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            ...(page ? { _method: 'put' } : {}),
        }));
        form.post(page ? `${BASE}/${page.public_id}` : BASE, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.setData('image', null),
        });
    };

    const heading = page ? `Ubah ${page.title}` : 'Tambah halaman';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: BASE },
                { title: 'Halaman', href: BASE },
                { title: page ? 'Ubah' : 'Tambah', href: '#' },
            ]}
        >
            <Head title={heading} />
            <PageHeader
                title={heading}
                description={
                    page?.is_system
                        ? `Halaman sistem (${page.type.label}). Bisa diubah dan diarsipkan, tetapi tidak dihapus.`
                        : 'Halaman tambahan di website sekolah.'
                }
            />

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                <div className="min-w-0 space-y-6 lg:col-span-2">
                    <FormCard title="Isi halaman">
                        <div className="grid gap-2">
                            <Label htmlFor="page-title">Judul</Label>
                            <Input
                                id="page-title"
                                maxLength={150}
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                aria-invalid={!!form.errors.title}
                            />
                            <InputError message={form.errors.title} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="page-content">Isi</Label>
                            <RichTextEditor
                                id="page-content"
                                value={form.data.content}
                                onChange={(html) =>
                                    form.setData('content', html)
                                }
                                invalid={!!form.errors.content}
                            />
                            <InputError message={form.errors.content} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="page-excerpt">
                                Ringkasan (opsional)
                            </Label>
                            <Textarea
                                id="page-excerpt"
                                rows={2}
                                maxLength={300}
                                value={form.data.excerpt}
                                onChange={(e) =>
                                    form.setData('excerpt', e.target.value)
                                }
                            />
                            <InputError message={form.errors.excerpt} />
                        </div>
                    </FormCard>
                    <SeoFields
                        slug={form.data.slug}
                        seoTitle={form.data.seo_title}
                        seoDescription={form.data.seo_description}
                        onChange={(key, value) => form.setData(key, value)}
                        errors={form.errors}
                        urlPrefix="/"
                        slugLocked={page?.is_system}
                    />
                </div>

                <aside className="min-w-0 space-y-6">
                    <PublishCard
                        statuses={statuses}
                        current={page?.status ?? null}
                        status={form.data.status}
                        publishAt={form.data.publish_at}
                        onStatusChange={(v) =>
                            form.setData('status', v as typeof form.data.status)
                        }
                        onPublishAtChange={(v) => form.setData('publish_at', v)}
                        errors={form.errors}
                        processing={form.processing}
                        previewUrl={
                            page
                                ? `${BASE}/${page.public_id}/pratinjau`
                                : undefined
                        }
                        updatedAt={page?.updated_at}
                    />
                    <FormCard title="Menu">
                        <label className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.show_in_menu}
                                onCheckedChange={(c) =>
                                    form.setData('show_in_menu', c === true)
                                }
                            />
                            Tampilkan di menu website
                        </label>
                        <div className="grid gap-2">
                            <Label htmlFor="page-position">Urutan menu</Label>
                            <Input
                                id="page-position"
                                type="number"
                                min={0}
                                max={999}
                                value={form.data.position}
                                onChange={(e) =>
                                    form.setData(
                                        'position',
                                        Number(e.target.value),
                                    )
                                }
                                className="w-28"
                            />
                            <InputError message={form.errors.position} />
                        </div>
                    </FormCard>
                    <FormCard title="Gambar utama">
                        <ImageField
                            id="page-image"
                            current={page?.image?.url ?? null}
                            file={form.data.image}
                            removed={form.data.remove_image}
                            onFileChange={(file) => form.setData('image', file)}
                            onRemovedChange={(v) =>
                                form.setData('remove_image', v)
                            }
                            error={form.errors.image}
                        />
                    </FormCard>
                </aside>
            </form>
        </AppLayout>
    );
}
