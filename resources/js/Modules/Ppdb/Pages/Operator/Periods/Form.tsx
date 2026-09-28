import { Head, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import { formatDate } from '@/lib/format';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import type { PeriodRow } from '@/Modules/Ppdb/types';

type Props = { period: PeriodRow | null; academicYears: Option[] };

type RequirementForm = {
    public_id: string | null;
    name: string;
    description: string;
    is_required: boolean;
};
type TrackForm = {
    public_id: string | null;
    name: string;
    quota: number | '';
    description: string;
    requirements: RequirementForm[];
};

const BASE = '/operator/ppdb/periode';
const DEFAULT_REQUIREMENTS: RequirementForm[] = [
    {
        public_id: null,
        name: 'Akta kelahiran',
        description: '',
        is_required: true,
    },
    {
        public_id: null,
        name: 'Kartu keluarga',
        description: '',
        is_required: true,
    },
    {
        public_id: null,
        name: 'Pas foto',
        description: 'Latar merah/biru',
        is_required: true,
    },
];

const local = (iso: string | null) =>
    iso ? formatDate(iso, "yyyy-MM-dd'T'HH:mm") : '';

function Card({
    title,
    actions,
    children,
}: {
    title: string;
    actions?: ReactNode;
    children: ReactNode;
}) {
    return (
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <header className="flex flex-wrap items-center justify-between gap-2">
                <h2 className="text-sm font-semibold text-slate-900">
                    {title}
                </h2>
                {actions}
            </header>
            {children}
        </section>
    );
}

export default function PeriodForm({ period, academicYears }: Props) {
    const form = useForm({
        name: period?.name ?? '',
        academic_year:
            period?.academic_year.public_id ?? academicYears[0]?.value ?? '',
        grade: (period?.grade ?? 7) as number | '',
        opens_at: local(period?.opens_at ?? null),
        closes_at: local(period?.closes_at ?? null),
        announces_at: local(period?.announces_at ?? null),
        description: period?.description ?? '',
        is_active: period?.is_active ?? true,
        tracks: (period?.tracks.map((t) => ({
            public_id: t.public_id,
            name: t.name,
            quota: t.quota,
            description: t.description ?? '',
            requirements: t.requirements.map((r) => ({
                public_id: r.public_id,
                name: r.name,
                description: r.description ?? '',
                is_required: r.is_required,
            })),
        })) ?? [
            {
                public_id: null,
                name: 'Reguler',
                quota: 100,
                description: '',
                requirements: DEFAULT_REQUIREMENTS,
            },
        ]) as TrackForm[],
    });
    const errors = form.errors as Record<string, string | undefined>;

    const setTrack = (i: number, patch: Partial<TrackForm>) =>
        form.setData(
            'tracks',
            form.data.tracks.map((t, j) => (j === i ? { ...t, ...patch } : t)),
        );
    const setRequirement = (
        i: number,
        k: number,
        patch: Partial<RequirementForm>,
    ) =>
        setTrack(i, {
            requirements: form.data.tracks[i].requirements.map((r, j) =>
                j === k ? { ...r, ...patch } : r,
            ),
        });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (period) {
            form.put(`${BASE}/${period.public_id}`, { preserveScroll: true });
        } else {
            form.post(BASE, { preserveScroll: true });
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'PPDB', href: '/operator/ppdb' },
                { title: 'Periode', href: BASE },
                { title: period ? 'Ubah' : 'Tambah', href: '#' },
            ]}
        >
            <Head
                title={period ? `Ubah ${period.name}` : 'Tambah periode PPDB'}
            />
            <PageHeader
                title={period ? `Ubah ${period.name}` : 'Tambah periode PPDB'}
            />

            <form onSubmit={submit} className="max-w-4xl space-y-6">
                <Card title="Periode">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="p-name">Nama periode</Label>
                            <Input
                                id="p-name"
                                maxLength={100}
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="PPDB 2027/2028 Gelombang 1"
                                aria-invalid={!!errors.name}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="p-year">Tahun ajaran tujuan</Label>
                            <NativeSelect
                                id="p-year"
                                value={form.data.academic_year}
                                onChange={(e) =>
                                    form.setData(
                                        'academic_year',
                                        e.target.value,
                                    )
                                }
                            >
                                {academicYears.map((y) => (
                                    <option key={y.value} value={y.value}>
                                        {y.label}
                                    </option>
                                ))}
                            </NativeSelect>
                            <p className="text-xs text-slate-500">
                                Tahun ajaran baru dibuat di Master Data › Tahun
                                Ajaran.
                            </p>
                            <InputError message={errors.academic_year} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="p-grade">Tingkat tujuan</Label>
                            <Input
                                id="p-grade"
                                type="number"
                                min={1}
                                max={12}
                                value={form.data.grade}
                                onChange={(e) =>
                                    form.setData(
                                        'grade',
                                        e.target.value === ''
                                            ? ''
                                            : Number(e.target.value),
                                    )
                                }
                            />
                            <InputError message={errors.grade} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="p-open">Dibuka</Label>
                            <Input
                                id="p-open"
                                type="datetime-local"
                                value={form.data.opens_at}
                                onChange={(e) =>
                                    form.setData('opens_at', e.target.value)
                                }
                            />
                            <InputError message={errors.opens_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="p-close">Ditutup</Label>
                            <Input
                                id="p-close"
                                type="datetime-local"
                                value={form.data.closes_at}
                                onChange={(e) =>
                                    form.setData('closes_at', e.target.value)
                                }
                            />
                            <InputError message={errors.closes_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="p-announce">
                                Pengumuman hasil (opsional)
                            </Label>
                            <Input
                                id="p-announce"
                                type="datetime-local"
                                value={form.data.announces_at}
                                onChange={(e) =>
                                    form.setData('announces_at', e.target.value)
                                }
                            />
                            <InputError message={errors.announces_at} />
                        </div>
                        <label className="flex items-center gap-2 self-end pb-2 text-sm">
                            <Checkbox
                                checked={form.data.is_active}
                                onCheckedChange={(c) =>
                                    form.setData('is_active', c === true)
                                }
                            />
                            Aktif (tampil bila tanggalnya berjalan)
                        </label>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="p-desc">
                            Informasi untuk pendaftar (opsional)
                        </Label>
                        <Textarea
                            id="p-desc"
                            rows={4}
                            maxLength={5000}
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                    </div>
                </Card>

                <Card
                    title="Jalur, kuota & dokumen"
                    actions={
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                form.setData('tracks', [
                                    ...form.data.tracks,
                                    {
                                        public_id: null,
                                        name: '',
                                        quota: 10,
                                        description: '',
                                        requirements: DEFAULT_REQUIREMENTS,
                                    },
                                ])
                            }
                        >
                            <Plus />
                            Tambah jalur
                        </Button>
                    }
                >
                    <InputError message={errors.tracks} />
                    {form.data.tracks.map((track, i) => (
                        <fieldset
                            key={track.public_id ?? `new-${i}`}
                            className="space-y-3 rounded-xl border border-slate-200 p-4"
                        >
                            <div className="grid gap-3 sm:grid-cols-[1fr_8rem_auto] sm:items-end">
                                <div className="grid gap-2">
                                    <Label htmlFor={`t-${i}-name`}>
                                        Nama jalur
                                    </Label>
                                    <Input
                                        id={`t-${i}-name`}
                                        maxLength={60}
                                        value={track.name}
                                        onChange={(e) =>
                                            setTrack(i, {
                                                name: e.target.value,
                                            })
                                        }
                                        placeholder="Prestasi"
                                    />
                                    <InputError
                                        message={errors[`tracks.${i}.name`]}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor={`t-${i}-quota`}>
                                        Kuota
                                    </Label>
                                    <Input
                                        id={`t-${i}-quota`}
                                        type="number"
                                        min={0}
                                        value={track.quota}
                                        onChange={(e) =>
                                            setTrack(i, {
                                                quota:
                                                    e.target.value === ''
                                                        ? ''
                                                        : Number(
                                                              e.target.value,
                                                          ),
                                            })
                                        }
                                    />
                                    <InputError
                                        message={errors[`tracks.${i}.quota`]}
                                    />
                                </div>
                                {form.data.tracks.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                                        onClick={() =>
                                            form.setData(
                                                'tracks',
                                                form.data.tracks.filter(
                                                    (_, j) => j !== i,
                                                ),
                                            )
                                        }
                                        aria-label={`Hapus jalur ${track.name}`}
                                    >
                                        <Trash2 />
                                    </Button>
                                )}
                            </div>
                            <Input
                                maxLength={255}
                                value={track.description}
                                onChange={(e) =>
                                    setTrack(i, { description: e.target.value })
                                }
                                placeholder="Keterangan jalur (opsional)"
                                aria-label="Keterangan jalur"
                            />
                            <div className="space-y-2">
                                <p className="text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                    Dokumen
                                </p>
                                {track.requirements.map((req, k) => (
                                    <div
                                        key={req.public_id ?? `r-${k}`}
                                        className="grid gap-2 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-center"
                                    >
                                        <Input
                                            maxLength={100}
                                            value={req.name}
                                            onChange={(e) =>
                                                setRequirement(i, k, {
                                                    name: e.target.value,
                                                })
                                            }
                                            placeholder="Nama dokumen"
                                            aria-label="Nama dokumen"
                                        />
                                        <Input
                                            maxLength={255}
                                            value={req.description}
                                            onChange={(e) =>
                                                setRequirement(i, k, {
                                                    description: e.target.value,
                                                })
                                            }
                                            placeholder="Keterangan (opsional)"
                                            aria-label="Keterangan dokumen"
                                        />
                                        <label className="flex items-center gap-2 text-sm whitespace-nowrap">
                                            <Checkbox
                                                checked={req.is_required}
                                                onCheckedChange={(c) =>
                                                    setRequirement(i, k, {
                                                        is_required: c === true,
                                                    })
                                                }
                                            />
                                            Wajib
                                        </label>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            className="size-8 text-slate-400"
                                            onClick={() =>
                                                setTrack(i, {
                                                    requirements:
                                                        track.requirements.filter(
                                                            (_, j) => j !== k,
                                                        ),
                                                })
                                            }
                                            aria-label={`Hapus ${req.name}`}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                ))}
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() =>
                                        setTrack(i, {
                                            requirements: [
                                                ...track.requirements,
                                                {
                                                    public_id: null,
                                                    name: '',
                                                    description: '',
                                                    is_required: true,
                                                },
                                            ],
                                        })
                                    }
                                >
                                    <Plus />
                                    Tambah dokumen
                                </Button>
                            </div>
                        </fieldset>
                    ))}
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Simpan periode
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
