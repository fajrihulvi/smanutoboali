import { Head, useForm } from '@inertiajs/react';
import { FileText, Trash2, Undo2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StudentPicker } from '@/Components/shared/StudentPicker';
import type { PickedStudent } from '@/Components/shared/StudentPicker';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import type { PortfolioRow } from '@/Modules/Portfolio/types';

type Props = {
    base: string;
    portfolio: PortfolioRow | null;
    categories: Option[];
    maxUploadKb: number;
    maxFiles: number;
};

const ACCEPT = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

export default function PortfolioForm({
    base,
    portfolio,
    categories,
    maxUploadKb,
    maxFiles,
}: Props) {
    const { confirmDelete, dialog } = useConfirmDelete();
    const form = useForm({
        category: portfolio?.category.code ?? categories[0]?.value ?? '',
        title: portfolio?.title ?? '',
        description: portfolio?.description ?? '',
        date: portfolio?.date ?? new Date().toLocaleDateString('en-CA'),
        visibility: portfolio?.visibility.value ?? 'DRAFT',
        picked: (portfolio?.students ?? []) as PickedStudent[],
        files: [] as File[],
        remove_files: [] as string[],
    });

    const kept = (portfolio?.files ?? []).filter(
        (file) => !form.data.remove_files.includes(file.public_id),
    );
    const room = Math.max(0, maxFiles - kept.length);
    const error = (key: string) => (form.errors as Record<string, string>)[key];

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform(({ picked, ...data }) => ({
            ...data,
            students: picked.map((student) => student.public_id),
            ...(portfolio ? { _method: 'put' } : {}),
        }));
        form.post(portfolio ? `${base}/${portfolio.public_id}` : base, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({
                    ...data,
                    files: [],
                    remove_files: [],
                })),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Portofolio', href: base },
                { title: portfolio ? 'Ubah' : 'Tambah', href: '#' },
            ]}
        >
            <Head
                title={
                    portfolio ? `Ubah ${portfolio.title}` : 'Tambah portofolio'
                }
            />

            <PageHeader
                title={portfolio ? 'Ubah portofolio' : 'Tambah portofolio'}
                description="Satu portofolio bisa untuk satu atau beberapa siswa (mis. proyek kelompok)."
                actions={
                    portfolio?.can.update && (
                        <Button
                            variant="outline"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() =>
                                confirmDelete({
                                    url: `${base}/${portfolio.public_id}`,
                                    title: `Hapus "${portfolio.title}"?`,
                                    description:
                                        'Portofolio dan semua filenya dihapus untuk semua siswa terkait.',
                                })
                            }
                        >
                            <Trash2 />
                            Hapus
                        </Button>
                    )
                }
            />

            <form
                onSubmit={submit}
                className="grid max-w-4xl gap-6 lg:grid-cols-5"
            >
                <section className="min-w-0 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-3">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="portfolio-category">Kategori</Label>
                            <NativeSelect
                                id="portfolio-category"
                                value={form.data.category}
                                onChange={(e) =>
                                    form.setData('category', e.target.value)
                                }
                            >
                                {categories.map((category) => (
                                    <option
                                        key={category.value}
                                        value={category.value}
                                    >
                                        {category.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.category} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="portfolio-date">Tanggal</Label>
                            <Input
                                id="portfolio-date"
                                type="date"
                                max={new Date().toLocaleDateString('en-CA')}
                                value={form.data.date}
                                onChange={(e) =>
                                    form.setData('date', e.target.value)
                                }
                                aria-invalid={!!form.errors.date}
                            />
                            <InputError message={form.errors.date} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="portfolio-title">Judul</Label>
                        <Input
                            id="portfolio-title"
                            maxLength={150}
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            placeholder="Maket tata surya"
                            aria-invalid={!!form.errors.title}
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="portfolio-description">
                            Deskripsi (opsional)
                        </Label>
                        <Textarea
                            id="portfolio-description"
                            rows={4}
                            maxLength={2000}
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label>File (foto / PDF)</Label>
                        {kept.length > 0 ||
                        form.data.remove_files.length > 0 ? (
                            <ul className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                {(portfolio?.files ?? []).map((file) => {
                                    const removed =
                                        form.data.remove_files.includes(
                                            file.public_id,
                                        );

                                    return (
                                        <li
                                            key={file.public_id}
                                            className={cn(
                                                'relative overflow-hidden rounded-lg border border-slate-200',
                                                removed && 'opacity-40',
                                            )}
                                        >
                                            {file.is_image ? (
                                                <img
                                                    src={file.thumb_url}
                                                    alt={file.name}
                                                    className="aspect-square w-full object-cover"
                                                />
                                            ) : (
                                                <div className="flex aspect-square flex-col items-center justify-center gap-1 bg-slate-50 p-2 text-center text-xs text-slate-600">
                                                    <FileText className="size-6 text-brand-500" />
                                                    <span className="line-clamp-2 break-all">
                                                        {file.name}
                                                    </span>
                                                </div>
                                            )}
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    form.setData(
                                                        'remove_files',
                                                        removed
                                                            ? form.data.remove_files.filter(
                                                                  (id) =>
                                                                      id !==
                                                                      file.public_id,
                                                              )
                                                            : [
                                                                  ...form.data
                                                                      .remove_files,
                                                                  file.public_id,
                                                              ],
                                                    )
                                                }
                                                className="absolute top-1 right-1 flex size-7 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow hover:bg-white"
                                                aria-label={
                                                    removed
                                                        ? `Batalkan hapus ${file.name}`
                                                        : `Hapus ${file.name}`
                                                }
                                            >
                                                {removed ? (
                                                    <Undo2 className="size-3.5" />
                                                ) : (
                                                    <Trash2 className="size-3.5" />
                                                )}
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : null}
                        {room > 0 ? (
                            <FileUploader
                                value={form.data.files}
                                onChange={(files) =>
                                    form.setData('files', files.slice(0, room))
                                }
                                accept={ACCEPT}
                                multiple
                                maxFiles={room}
                                maxSizeKb={maxUploadKb}
                                hint={`Foto (JPG/PNG/WebP) atau PDF · maks ${room} file lagi · ${Math.round(maxUploadKb / 1024)} MB per file`}
                                error={form.errors.files ?? error('files.0')}
                            />
                        ) : (
                            <p className="text-xs text-slate-500">
                                Batas {maxFiles} file tercapai. Hapus file lama
                                untuk menambah.
                            </p>
                        )}
                    </div>
                </section>

                <aside className="min-w-0 space-y-6 lg:col-span-2">
                    <section className="space-y-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <Label>Siswa</Label>
                        <StudentPicker
                            value={form.data.picked}
                            onChange={(picked) =>
                                form.setData('picked', picked)
                            }
                            searchUrl={`${base}/siswa`}
                            invalid={!!error('students')}
                        />
                        <InputError message={error('students')} />
                    </section>

                    <section className="space-y-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <Label>Status</Label>
                        <div
                            className="grid grid-cols-2 gap-2"
                            role="radiogroup"
                        >
                            {[
                                {
                                    value: 'DRAFT' as const,
                                    label: 'Draf',
                                    hint: 'Belum terlihat orang tua',
                                },
                                {
                                    value: 'PUBLISHED' as const,
                                    label: 'Terbit',
                                    hint: 'Orang tua diberi tahu',
                                },
                            ].map((option) => (
                                <label
                                    key={option.value}
                                    className={cn(
                                        'cursor-pointer rounded-xl border p-3 text-sm transition-colors duration-150',
                                        form.data.visibility === option.value
                                            ? 'border-brand-600 bg-brand-50'
                                            : 'border-slate-200 hover:border-brand-300',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name="visibility"
                                        className="sr-only"
                                        checked={
                                            form.data.visibility ===
                                            option.value
                                        }
                                        onChange={() =>
                                            form.setData(
                                                'visibility',
                                                option.value,
                                            )
                                        }
                                    />
                                    <span className="block font-medium text-slate-900">
                                        {option.label}
                                    </span>
                                    <span className="block text-xs text-slate-500">
                                        {option.hint}
                                    </span>
                                </label>
                            ))}
                        </div>
                        <InputError message={form.errors.visibility} />
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Simpan
                        </Button>
                    </section>
                </aside>
            </form>
            {dialog}
        </AppLayout>
    );
}
