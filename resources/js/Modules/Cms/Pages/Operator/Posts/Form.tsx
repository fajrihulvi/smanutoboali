import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { FormCard } from '@/Modules/Cms/Components/FormCard';
import { ImageField } from '@/Modules/Cms/Components/ImageField';
import {
    PublishCard,
    toLocalInput,
} from '@/Modules/Cms/Components/PublishCard';
import { RichTextEditor } from '@/Modules/Cms/Components/RichTextEditor';
import { SeoFields } from '@/Modules/Cms/Components/SeoFields';
import type { PostRow, PostTypeInfo } from '@/Modules/Cms/types';

type Props = {
    type: PostTypeInfo;
    base: string;
    post: PostRow | null;
    statuses: Option[];
    categories: Option[];
};

export default function CmsPostForm({
    type,
    base,
    post,
    statuses,
    categories,
}: Props) {
    const isAgenda = type.value === 'AGENDA';
    const noun = type.label.toLowerCase();

    const form = useForm({
        title: post?.title ?? '',
        slug: post?.slug ?? '',
        category: post?.category?.public_id ?? '',
        excerpt: post?.excerpt ?? '',
        content: post?.content ?? '',
        seo_title: post?.seo_title ?? '',
        seo_description: post?.seo_description ?? '',
        is_featured: post?.is_featured ?? false,
        event_starts_at: toLocalInput(post?.event_starts_at ?? null),
        event_ends_at: toLocalInput(post?.event_ends_at ?? null),
        event_location: post?.event_location ?? '',
        status: post?.status.value ?? 'DRAFT',
        publish_at: toLocalInput(post?.publish_at ?? null),
        image: null as File | null,
        remove_image: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            ...(post ? { _method: 'put' } : {}),
        }));
        form.post(post ? `${base}/${post.public_id}` : base, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.setData('image', null),
        });
    };

    const heading = post ? `Ubah ${noun}` : `Tambah ${noun}`;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: type.label, href: base },
                { title: post ? 'Ubah' : 'Tambah', href: '#' },
            ]}
        >
            <Head title={heading} />
            <PageHeader title={heading} />

            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-3">
                <div className="min-w-0 space-y-6 lg:col-span-2">
                    <FormCard title={`Isi ${noun}`}>
                        <div className="grid gap-2">
                            <Label htmlFor="post-title">Judul</Label>
                            <Input
                                id="post-title"
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
                            <Label htmlFor="post-excerpt">
                                Ringkasan (opsional)
                            </Label>
                            <Textarea
                                id="post-excerpt"
                                rows={2}
                                maxLength={300}
                                value={form.data.excerpt}
                                onChange={(e) =>
                                    form.setData('excerpt', e.target.value)
                                }
                                placeholder="Tampil di daftar dan kartu. Kosongkan untuk memakai awal isi."
                            />
                            <InputError message={form.errors.excerpt} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="post-content">Isi</Label>
                            <RichTextEditor
                                id="post-content"
                                value={form.data.content}
                                onChange={(html) =>
                                    form.setData('content', html)
                                }
                                invalid={!!form.errors.content}
                            />
                            <InputError message={form.errors.content} />
                        </div>
                    </FormCard>

                    {isAgenda && (
                        <FormCard title="Waktu & tempat">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="post-starts">Mulai</Label>
                                    <Input
                                        id="post-starts"
                                        type="datetime-local"
                                        value={form.data.event_starts_at}
                                        onChange={(e) =>
                                            form.setData(
                                                'event_starts_at',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={
                                            !!form.errors.event_starts_at
                                        }
                                    />
                                    <InputError
                                        message={form.errors.event_starts_at}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="post-ends">
                                        Selesai (opsional)
                                    </Label>
                                    <Input
                                        id="post-ends"
                                        type="datetime-local"
                                        value={form.data.event_ends_at}
                                        onChange={(e) =>
                                            form.setData(
                                                'event_ends_at',
                                                e.target.value,
                                            )
                                        }
                                        aria-invalid={
                                            !!form.errors.event_ends_at
                                        }
                                    />
                                    <InputError
                                        message={form.errors.event_ends_at}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="post-location">Lokasi</Label>
                                <Input
                                    id="post-location"
                                    maxLength={150}
                                    value={form.data.event_location}
                                    onChange={(e) =>
                                        form.setData(
                                            'event_location',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Aula sekolah"
                                />
                                <InputError
                                    message={form.errors.event_location}
                                />
                            </div>
                        </FormCard>
                    )}

                    <SeoFields
                        slug={form.data.slug}
                        seoTitle={form.data.seo_title}
                        seoDescription={form.data.seo_description}
                        onChange={(key, value) => form.setData(key, value)}
                        errors={form.errors}
                        urlPrefix={`/${type.segment}/`}
                    />
                </div>

                <aside className="min-w-0 space-y-6">
                    <PublishCard
                        statuses={statuses}
                        current={post?.status ?? null}
                        status={form.data.status}
                        publishAt={form.data.publish_at}
                        onStatusChange={(v) =>
                            form.setData('status', v as typeof form.data.status)
                        }
                        onPublishAtChange={(v) => form.setData('publish_at', v)}
                        errors={form.errors}
                        processing={form.processing}
                        previewUrl={
                            post
                                ? `${base}/${post.public_id}/pratinjau`
                                : undefined
                        }
                        updatedAt={post?.updated_at}
                    />
                    <FormCard title="Pengaturan">
                        <div className="grid gap-2">
                            <Label htmlFor="post-category">Kategori</Label>
                            <NativeSelect
                                id="post-category"
                                value={form.data.category}
                                onChange={(e) =>
                                    form.setData('category', e.target.value)
                                }
                            >
                                <option value="">Tanpa kategori</option>
                                {categories.map((c) => (
                                    <option key={c.value} value={c.value}>
                                        {c.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <Link
                                href="/operator/cms/kategori"
                                className="text-xs text-brand-700 hover:underline"
                            >
                                Kelola kategori
                            </Link>
                            <InputError message={form.errors.category} />
                        </div>
                        <label className="flex items-start gap-2 text-sm">
                            <Checkbox
                                checked={form.data.is_featured}
                                onCheckedChange={(c) =>
                                    form.setData('is_featured', c === true)
                                }
                                className="mt-0.5"
                            />
                            <span>
                                <span className="block font-medium text-slate-900">
                                    Unggulan
                                </span>
                                <span className="block text-xs text-slate-500">
                                    Ditonjolkan di beranda website.
                                </span>
                            </span>
                        </label>
                    </FormCard>
                    <FormCard title="Gambar utama">
                        <ImageField
                            id="post-image"
                            current={post?.image?.url ?? null}
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
