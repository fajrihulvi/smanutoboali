import { Head, useForm } from '@inertiajs/react';
import { FileText, Trash2, Undo2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { Option } from '@/Modules/Academic/types';
import type { AnnouncementRow } from '@/Modules/Announcement/types';

type AudienceOption = Option & { hint: string };

type Props = {
    base: string;
    announcement: AnnouncementRow | null;
    audiences: AudienceOption[];
    roles: Option[];
    classrooms: Option[];
    maxAttachments: number;
};

type PublishMode = 'draft' | 'now' | 'schedule';

const ACCEPT = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

/** ISO → nilai input datetime-local ("2026-10-01T07:00"). */
function toLocalInput(iso: string | null): string {
    return iso ? formatDate(iso, "yyyy-MM-dd'T'HH:mm") : '';
}

function Card({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 className="text-sm font-semibold text-slate-900">{title}</h2>
            {children}
        </section>
    );
}

export default function AnnouncementForm({
    base,
    announcement,
    audiences,
    roles,
    classrooms,
    maxAttachments,
}: Props) {
    const initialMode: PublishMode = !announcement
        ? 'now'
        : announcement.status.value === 'DRAF'
          ? 'draft'
          : announcement.status.value === 'TERJADWAL'
            ? 'schedule'
            : 'now';

    const form = useForm({
        title: announcement?.title ?? '',
        body: announcement?.body ?? '',
        audience: announcement?.audience.value ?? 'SEMUA',
        roles: announcement?.roles ?? ([] as string[]),
        classrooms:
            announcement?.classrooms.map((c) => c.public_id) ??
            ([] as string[]),
        pinned: announcement?.pinned ?? false,
        publish: initialMode,
        publish_at:
            initialMode === 'schedule'
                ? toLocalInput(announcement?.publish_at ?? null)
                : '',
        files: [] as File[],
        remove_files: [] as string[],
    });

    const existing = announcement?.attachments ?? [];
    const kept = existing.filter(
        (file) => !form.data.remove_files.includes(file.public_id),
    );
    const room = Math.max(0, maxAttachments - kept.length);
    const error = (key: string) =>
        (form.errors as Record<string, string | undefined>)[key];

    const toggle = (key: 'roles' | 'classrooms', value: string) =>
        form.setData(
            key,
            form.data[key].includes(value)
                ? form.data[key].filter((v) => v !== value)
                : [...form.data[key], value],
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            ...(announcement ? { _method: 'put' } : {}),
        }));
        form.post(announcement ? `${base}/${announcement.public_id}` : base, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const published = announcement?.status.value === 'TERBIT';

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pengumuman', href: base },
                { title: announcement ? 'Ubah' : 'Buat', href: '#' },
            ]}
        >
            <Head
                title={announcement ? 'Ubah pengumuman' : 'Buat pengumuman'}
            />
            <PageHeader
                title={announcement ? 'Ubah pengumuman' : 'Buat pengumuman'}
                description="Isi berupa teks biasa; baris baru dipertahankan."
            />

            <form
                onSubmit={submit}
                className="grid max-w-5xl gap-6 lg:grid-cols-5"
            >
                <div className="min-w-0 space-y-6 lg:col-span-3">
                    <Card title="Isi pengumuman">
                        <div className="grid gap-2">
                            <Label htmlFor="announcement-title">Judul</Label>
                            <Input
                                id="announcement-title"
                                maxLength={150}
                                value={form.data.title}
                                onChange={(e) =>
                                    form.setData('title', e.target.value)
                                }
                                placeholder="Libur Hari Maulid Nabi"
                                aria-invalid={!!form.errors.title}
                            />
                            <InputError message={form.errors.title} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="announcement-body">Isi</Label>
                            <Textarea
                                id="announcement-body"
                                rows={10}
                                className="min-h-48"
                                maxLength={10000}
                                value={form.data.body}
                                onChange={(e) =>
                                    form.setData('body', e.target.value)
                                }
                                aria-invalid={!!form.errors.body}
                            />
                            <InputError message={form.errors.body} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Lampiran (foto / PDF)</Label>
                            {existing.length > 0 && (
                                <ul className="space-y-2">
                                    {existing.map((file) => {
                                        const removed =
                                            form.data.remove_files.includes(
                                                file.public_id,
                                            );

                                        return (
                                            <li
                                                key={file.public_id}
                                                className={cn(
                                                    'flex items-center gap-3 rounded-xl border border-slate-200 p-2 text-sm',
                                                    removed && 'opacity-50',
                                                )}
                                            >
                                                {file.is_image ? (
                                                    <img
                                                        src={file.thumb_url}
                                                        alt=""
                                                        className="size-10 rounded-lg object-cover"
                                                    />
                                                ) : (
                                                    <FileText className="size-8 text-brand-500" />
                                                )}
                                                <span className="min-w-0 flex-1 truncate">
                                                    {file.name}
                                                </span>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8"
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
                                                                      ...form
                                                                          .data
                                                                          .remove_files,
                                                                      file.public_id,
                                                                  ],
                                                        )
                                                    }
                                                    aria-label={
                                                        removed
                                                            ? `Batalkan hapus ${file.name}`
                                                            : `Hapus ${file.name}`
                                                    }
                                                >
                                                    {removed ? (
                                                        <Undo2 />
                                                    ) : (
                                                        <Trash2 />
                                                    )}
                                                </Button>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                            {room > 0 ? (
                                <FileUploader
                                    value={form.data.files}
                                    onChange={(files) =>
                                        form.setData(
                                            'files',
                                            files.slice(0, room),
                                        )
                                    }
                                    accept={ACCEPT}
                                    multiple
                                    maxFiles={room}
                                    maxSizeKb={10240}
                                    hint={`Maks ${room} file lagi · 10 MB per file`}
                                    error={error('files') ?? error('files.0')}
                                />
                            ) : (
                                <p className="text-xs text-slate-500">
                                    Batas {maxAttachments} lampiran tercapai.
                                </p>
                            )}
                        </div>
                    </Card>
                </div>

                <aside className="min-w-0 space-y-6 lg:col-span-2">
                    <Card title="Ditujukan untuk">
                        <div role="radiogroup" className="grid gap-2">
                            {audiences.map((option) => (
                                <label
                                    key={option.value}
                                    className={cn(
                                        'cursor-pointer rounded-xl border p-3 text-sm transition-colors duration-150',
                                        form.data.audience === option.value
                                            ? 'border-brand-600 bg-brand-50'
                                            : 'border-slate-200 hover:border-brand-300',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name="audience"
                                        className="sr-only"
                                        checked={
                                            form.data.audience === option.value
                                        }
                                        onChange={() =>
                                            form.setData(
                                                'audience',
                                                option.value as typeof form.data.audience,
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
                        <InputError message={form.errors.audience} />

                        {form.data.audience === 'PERAN' && (
                            <fieldset className="space-y-2">
                                <legend className="mb-1 text-xs font-medium text-slate-600">
                                    Peran
                                </legend>
                                {roles.map((role) => (
                                    <label
                                        key={role.value}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={form.data.roles.includes(
                                                role.value,
                                            )}
                                            onCheckedChange={() =>
                                                toggle('roles', role.value)
                                            }
                                        />
                                        {role.label}
                                    </label>
                                ))}
                                <InputError message={form.errors.roles} />
                            </fieldset>
                        )}

                        {form.data.audience === 'ROMBEL' && (
                            <fieldset className="space-y-2">
                                <legend className="mb-1 text-xs font-medium text-slate-600">
                                    Rombel (tahun ajaran aktif)
                                </legend>
                                {classrooms.length === 0 ? (
                                    <p className="text-sm text-slate-500">
                                        Belum ada rombel.
                                    </p>
                                ) : (
                                    <div className="grid max-h-56 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-3">
                                        {classrooms.map((classroom) => (
                                            <label
                                                key={classroom.value}
                                                className="flex items-center gap-2 text-sm"
                                            >
                                                <Checkbox
                                                    checked={form.data.classrooms.includes(
                                                        classroom.value,
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggle(
                                                            'classrooms',
                                                            classroom.value,
                                                        )
                                                    }
                                                />
                                                {classroom.label}
                                            </label>
                                        ))}
                                    </div>
                                )}
                                <InputError message={form.errors.classrooms} />
                            </fieldset>
                        )}
                    </Card>

                    <Card title="Penayangan">
                        <div role="radiogroup" className="grid gap-2 text-sm">
                            {(
                                [
                                    [
                                        'now',
                                        published
                                            ? 'Terbit'
                                            : 'Terbitkan sekarang',
                                    ],
                                    ['schedule', 'Jadwalkan'],
                                    ['draft', 'Simpan sebagai draf'],
                                ] as [PublishMode, string][]
                            ).map(([value, label]) => (
                                <label
                                    key={value}
                                    className="flex items-center gap-2"
                                >
                                    <input
                                        type="radio"
                                        name="publish"
                                        className="size-4 accent-brand-600"
                                        checked={form.data.publish === value}
                                        onChange={() =>
                                            form.setData('publish', value)
                                        }
                                    />
                                    {label}
                                </label>
                            ))}
                        </div>
                        {form.data.publish === 'schedule' && (
                            <div className="grid gap-2">
                                <Label htmlFor="announcement-publish-at">
                                    Waktu tayang
                                </Label>
                                <Input
                                    id="announcement-publish-at"
                                    type="datetime-local"
                                    value={form.data.publish_at}
                                    onChange={(e) =>
                                        form.setData(
                                            'publish_at',
                                            e.target.value,
                                        )
                                    }
                                    aria-invalid={!!form.errors.publish_at}
                                />
                                <InputError message={form.errors.publish_at} />
                            </div>
                        )}
                        <InputError message={form.errors.publish} />
                        <label className="flex items-start gap-2 text-sm">
                            <Checkbox
                                checked={form.data.pinned}
                                onCheckedChange={(checked) =>
                                    form.setData('pinned', checked === true)
                                }
                                className="mt-0.5"
                            />
                            <span>
                                <span className="block font-medium text-slate-900">
                                    Sematkan
                                </span>
                                <span className="block text-xs text-slate-500">
                                    Tampil paling atas di daftar pengumuman.
                                </span>
                            </span>
                        </label>
                        <p className="text-xs text-slate-500">
                            Penerima mendapat notifikasi sekali, saat pengumuman
                            terbit.
                        </p>
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            {form.data.publish === 'draft'
                                ? 'Simpan draf'
                                : form.data.publish === 'schedule'
                                  ? 'Jadwalkan'
                                  : published
                                    ? 'Simpan perubahan'
                                    : 'Terbitkan'}
                        </Button>
                    </Card>
                </aside>
            </form>
        </AppLayout>
    );
}
