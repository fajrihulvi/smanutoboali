import { Head, useForm } from '@inertiajs/react';
import { Trash2, Undo2 } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StudentPicker } from '@/Components/shared/StudentPicker';
import type { PickedStudent } from '@/Components/shared/StudentPicker';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import type { Option } from '@/Modules/Academic/types';
import type { ActivityRow } from '@/Modules/Activity/types';

type Props = {
    base: string;
    activity: ActivityRow | null;
    types: Option[];
    classrooms: Option[];
    maxUploadKb: number;
    maxPhotos: number;
};

const TYPE_HINT: Record<string, string> = {
    HARIAN: 'Rutinitas di kelas, mis. literasi pagi.',
    KELAS: 'Kegiatan satu/beberapa rombel, mis. kunjungan museum.',
    SEKOLAH: 'Seluruh siswa, mis. upacara atau pentas seni.',
};

export default function ActivityForm({
    base,
    activity,
    types,
    classrooms,
    maxUploadKb,
    maxPhotos,
}: Props) {
    const { confirmDelete, dialog } = useConfirmDelete();
    const form = useForm({
        title: activity?.title ?? '',
        type: activity?.type.value ?? types[0]?.value ?? 'KELAS',
        date: activity?.date ?? new Date().toLocaleDateString('en-CA'),
        start_time: activity?.start_time ?? '',
        end_time: activity?.end_time ?? '',
        location: activity?.location ?? '',
        description: activity?.description ?? '',
        status: activity?.status.value ?? 'DRAF',
        classrooms: (activity?.classrooms ?? []).map((c) => c.public_id),
        picked: (activity?.students ?? []) as PickedStudent[],
        photos: [] as File[],
        remove_photos: [] as string[],
    });
    const error = (key: string) => (form.errors as Record<string, string>)[key];
    const kept = (activity?.photos ?? []).filter(
        (p) => !form.data.remove_photos.includes(p.public_id),
    );
    const room = Math.max(0, maxPhotos - kept.length);
    const needsParticipants = form.data.type !== 'SEKOLAH';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform(({ picked, ...data }) => ({
            ...data,
            students: picked.map((s) => s.public_id),
            ...(activity ? { _method: 'put' } : {}),
        }));
        form.post(activity ? `${base}/${activity.public_id}` : base, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({
                    ...data,
                    photos: [],
                    remove_photos: [],
                })),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Kegiatan', href: base },
                { title: activity ? 'Ubah' : 'Tambah', href: '#' },
            ]}
        >
            <Head
                title={activity ? `Ubah ${activity.title}` : 'Tambah kegiatan'}
            />

            <PageHeader
                title={activity ? 'Ubah kegiatan' : 'Tambah kegiatan'}
                actions={
                    activity?.can.update && (
                        <Button
                            variant="outline"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() =>
                                confirmDelete({
                                    url: `${base}/${activity.public_id}`,
                                    title: `Hapus "${activity.title}"?`,
                                    description:
                                        'Kegiatan dan semua fotonya dihapus.',
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
                className="grid max-w-5xl gap-6 lg:grid-cols-5"
            >
                <section className="min-w-0 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-3">
                    <div className="grid gap-2">
                        <Label htmlFor="activity-title">Judul</Label>
                        <Input
                            id="activity-title"
                            maxLength={150}
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            aria-invalid={!!form.errors.title}
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <fieldset className="grid gap-2">
                        <legend className="mb-2 text-sm font-medium text-slate-900">
                            Jenis
                        </legend>
                        <div
                            className="grid gap-2 sm:grid-cols-3"
                            role="radiogroup"
                        >
                            {types.map((type) => (
                                <label
                                    key={type.value}
                                    className={cn(
                                        'cursor-pointer rounded-xl border p-3 text-sm transition-colors duration-150',
                                        form.data.type === type.value
                                            ? 'border-brand-600 bg-brand-50'
                                            : 'border-slate-200 hover:border-brand-300',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        className="sr-only"
                                        name="type"
                                        checked={form.data.type === type.value}
                                        onChange={() =>
                                            form.setData('type', type.value)
                                        }
                                    />
                                    <span className="block font-medium text-slate-900">
                                        {type.label}
                                    </span>
                                    <span className="block text-xs text-slate-500">
                                        {TYPE_HINT[type.value]}
                                    </span>
                                </label>
                            ))}
                        </div>
                        <InputError message={form.errors.type} />
                    </fieldset>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="activity-date">Tanggal</Label>
                            <Input
                                id="activity-date"
                                type="date"
                                value={form.data.date}
                                onChange={(e) =>
                                    form.setData('date', e.target.value)
                                }
                                aria-invalid={!!form.errors.date}
                            />
                            <InputError message={form.errors.date} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="activity-start">
                                Mulai (opsional)
                            </Label>
                            <Input
                                id="activity-start"
                                type="time"
                                value={form.data.start_time}
                                onChange={(e) =>
                                    form.setData('start_time', e.target.value)
                                }
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="activity-end">
                                Selesai (opsional)
                            </Label>
                            <Input
                                id="activity-end"
                                type="time"
                                value={form.data.end_time}
                                onChange={(e) =>
                                    form.setData('end_time', e.target.value)
                                }
                                aria-invalid={!!form.errors.end_time}
                            />
                            <InputError message={form.errors.end_time} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="activity-location">
                            Lokasi (opsional)
                        </Label>
                        <Input
                            id="activity-location"
                            maxLength={150}
                            value={form.data.location}
                            onChange={(e) =>
                                form.setData('location', e.target.value)
                            }
                            placeholder="Aula sekolah"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="activity-description">
                            Cerita kegiatan (opsional)
                        </Label>
                        <Textarea
                            id="activity-description"
                            rows={5}
                            maxLength={3000}
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Foto</Label>
                        {(activity?.photos ?? []).length > 0 && (
                            <ul className="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                {(activity?.photos ?? []).map((photo) => {
                                    const removed =
                                        form.data.remove_photos.includes(
                                            photo.public_id,
                                        );

                                    return (
                                        <li
                                            key={photo.public_id}
                                            className={cn(
                                                'relative overflow-hidden rounded-lg border border-slate-200',
                                                removed && 'opacity-40',
                                            )}
                                        >
                                            <img
                                                src={photo.url}
                                                alt={photo.name}
                                                className="aspect-square w-full object-cover"
                                            />
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    form.setData(
                                                        'remove_photos',
                                                        removed
                                                            ? form.data.remove_photos.filter(
                                                                  (id) =>
                                                                      id !==
                                                                      photo.public_id,
                                                              )
                                                            : [
                                                                  ...form.data
                                                                      .remove_photos,
                                                                  photo.public_id,
                                                              ],
                                                    )
                                                }
                                                className="absolute top-1 right-1 flex size-7 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow hover:bg-white"
                                                aria-label={
                                                    removed
                                                        ? 'Batalkan hapus foto'
                                                        : 'Hapus foto'
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
                        )}
                        {room > 0 && (
                            <FileUploader
                                value={form.data.photos}
                                onChange={(files) =>
                                    form.setData('photos', files.slice(0, room))
                                }
                                accept={[
                                    'image/jpeg',
                                    'image/png',
                                    'image/webp',
                                ]}
                                multiple
                                maxFiles={room}
                                maxSizeKb={maxUploadKb}
                                hint={`JPG/PNG/WebP · dikompres otomatis · maks ${room} foto lagi`}
                                error={form.errors.photos ?? error('photos.0')}
                            />
                        )}
                    </div>
                </section>

                <aside className="min-w-0 space-y-6 lg:col-span-2">
                    <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-900">
                            Peserta
                        </h2>
                        {needsParticipants ? (
                            <>
                                <div className="grid gap-2">
                                    <Label>Rombel</Label>
                                    {classrooms.length === 0 ? (
                                        <p className="text-sm text-slate-500">
                                            Tidak ada rombel yang Anda
                                            ajar/walikan.
                                        </p>
                                    ) : (
                                        <div className="flex flex-wrap gap-2">
                                            {classrooms.map((classroom) => {
                                                const checked =
                                                    form.data.classrooms.includes(
                                                        classroom.value,
                                                    );

                                                return (
                                                    <label
                                                        key={classroom.value}
                                                        className={cn(
                                                            'flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 text-sm',
                                                            checked
                                                                ? 'border-brand-600 bg-brand-50 text-brand-800'
                                                                : 'border-slate-200 text-slate-700',
                                                        )}
                                                    >
                                                        <Checkbox
                                                            checked={checked}
                                                            onCheckedChange={(
                                                                state,
                                                            ) =>
                                                                form.setData(
                                                                    'classrooms',
                                                                    state ===
                                                                        true
                                                                        ? [
                                                                              ...form
                                                                                  .data
                                                                                  .classrooms,
                                                                              classroom.value,
                                                                          ]
                                                                        : form.data.classrooms.filter(
                                                                              (
                                                                                  c,
                                                                              ) =>
                                                                                  c !==
                                                                                  classroom.value,
                                                                          ),
                                                                )
                                                            }
                                                        />
                                                        {classroom.label}
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    )}
                                    <InputError
                                        message={form.errors.classrooms}
                                    />
                                </div>
                                <div className="grid min-w-0 gap-2">
                                    <Label>Siswa tertentu (opsional)</Label>
                                    <StudentPicker
                                        value={form.data.picked}
                                        onChange={(picked) =>
                                            form.setData('picked', picked)
                                        }
                                        searchUrl={`${base}/siswa`}
                                        invalid={!!error('students')}
                                    />
                                    <InputError message={error('students')} />
                                </div>
                            </>
                        ) : (
                            <p className="text-sm text-slate-600">
                                Kegiatan sekolah berlaku untuk seluruh siswa dan
                                tampil di feed semua orang tua.
                            </p>
                        )}
                    </section>

                    <section className="space-y-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <Label>Status</Label>
                        <div
                            className="grid grid-cols-2 gap-2"
                            role="radiogroup"
                        >
                            {[
                                {
                                    value: 'DRAF' as const,
                                    label: 'Draf',
                                    hint: 'Hanya pembuat & operator',
                                },
                                {
                                    value: 'TERBIT' as const,
                                    label: 'Terbit',
                                    hint: 'Tampil di feed & kalender',
                                },
                            ].map((option) => (
                                <label
                                    key={option.value}
                                    className={cn(
                                        'cursor-pointer rounded-xl border p-3 text-sm transition-colors duration-150',
                                        form.data.status === option.value
                                            ? 'border-brand-600 bg-brand-50'
                                            : 'border-slate-200 hover:border-brand-300',
                                    )}
                                >
                                    <input
                                        type="radio"
                                        name="status"
                                        className="sr-only"
                                        checked={
                                            form.data.status === option.value
                                        }
                                        onChange={() =>
                                            form.setData('status', option.value)
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
