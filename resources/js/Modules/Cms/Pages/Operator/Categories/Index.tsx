import { Head, useForm } from '@inertiajs/react';
import { Plus, Tag } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { RowActions } from '@/Components/shared/RowActions';
import { Button } from '@/Components/ui/button';
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
import type { CategoryRow } from '@/Modules/Cms/types';

type Props = { categories: CategoryRow[] };

const BASE = '/operator/cms/kategori';

export default function CmsCategories({ categories }: Props) {
    const [editing, setEditing] = useState<{
        open: boolean;
        row: CategoryRow | null;
    }>({ open: false, row: null });
    const { confirmDelete, dialog } = useConfirmDelete();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: 'Kategori', href: BASE },
            ]}
        >
            <Head title="Kategori berita" />
            <PageHeader
                title="Kategori berita"
                description="Kelompokkan berita dan agenda, mis. Prestasi, Kegiatan, Pengumuman."
                actions={
                    <Button
                        onClick={() => setEditing({ open: true, row: null })}
                    >
                        <Plus />
                        Tambah kategori
                    </Button>
                }
            />

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {categories.length === 0 ? (
                    <EmptyState
                        icon={Tag}
                        title="Belum ada kategori"
                        description="Kategori bersifat opsional."
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {categories.map((row) => (
                            <li
                                key={row.public_id}
                                className="flex items-center gap-3 px-4 py-3 sm:px-5"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium text-slate-900">
                                        {row.name}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        /{row.slug} · {row.posts_count} konten
                                    </p>
                                </div>
                                <RowActions
                                    label={row.name}
                                    onEdit={() =>
                                        setEditing({ open: true, row })
                                    }
                                    onDelete={() =>
                                        confirmDelete({
                                            url: `${BASE}/${row.public_id}`,
                                            title: `Hapus kategori ${row.name}?`,
                                            description:
                                                'Berita dan agenda di kategori ini tetap ada, menjadi tanpa kategori.',
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
                <DialogContent className="sm:max-w-sm">
                    <CategoryForm
                        key={editing.row?.public_id ?? 'new'}
                        row={editing.row}
                        onClose={() => setEditing({ open: false, row: null })}
                    />
                </DialogContent>
            </Dialog>
            {dialog}
        </AppLayout>
    );
}

function CategoryForm({
    row,
    onClose,
}: {
    row: CategoryRow | null;
    onClose: () => void;
}) {
    const form = useForm({ name: row?.name ?? '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (row) {
            form.put(`${BASE}/${row.public_id}`, options);
        } else {
            form.post(BASE, options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>
                    {row ? 'Ubah kategori' : 'Tambah kategori'}
                </DialogTitle>
            </DialogHeader>
            <div className="grid gap-2">
                <Label htmlFor="category-name">Nama</Label>
                <Input
                    id="category-name"
                    maxLength={60}
                    value={form.data.name}
                    onChange={(e) => form.setData('name', e.target.value)}
                    placeholder="Prestasi"
                    aria-invalid={!!form.errors.name}
                />
                <InputError message={form.errors.name} />
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
