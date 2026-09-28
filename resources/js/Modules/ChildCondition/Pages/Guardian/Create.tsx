import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Baby } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import ParentLayout from '@/Layouts/ParentLayout';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import { TYPE_ICONS } from '@/Modules/ChildCondition/Components/ConditionBits';
import type { TypeOption } from '@/Modules/ChildCondition/types';

type ChildOption = {
    public_id: string;
    name: string;
    teachers: {
        public_id: string;
        name: string;
        label: string;
        homeroom: boolean;
    }[];
};

type Props = {
    children: ChildOption[];
    selected: string | null;
    types: TypeOption[];
    today: string;
};

const MAX_PHOTOS = 3;

function Step({
    number,
    title,
    children,
}: {
    number: number;
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <span className="flex size-6 items-center justify-center rounded-full bg-brand-600 text-xs text-white">
                    {number}
                </span>
                {title}
            </h2>
            {children}
        </section>
    );
}

export default function GuardianConditionCreate({
    children: childOptions,
    selected,
    types,
    today,
}: Props) {
    const initialChild =
        childOptions.find((c) => c.public_id === selected) ??
        (childOptions.length === 1 ? childOptions[0] : undefined);

    const form = useForm({
        student: initialChild?.public_id ?? '',
        type: '',
        description: '',
        valid_from: today,
        valid_until: '',
        target_teacher: initialChild?.teachers[0]?.public_id ?? '',
        photos: [] as File[],
    });

    const child = childOptions.find((c) => c.public_id === form.data.student);
    const error = (key: string) =>
        (form.errors as Record<string, string | undefined>)[key];

    const pickChild = (option: ChildOption) =>
        form.setData((data) => ({
            ...data,
            student: option.public_id,
            target_teacher: option.teachers[0]?.public_id ?? '',
        }));

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/orang-tua/kondisi-anak', {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <ParentLayout>
            <Head title="Laporkan kondisi anak" />

            <div className="flex items-center gap-2">
                <Button variant="ghost" size="icon" asChild>
                    <Link
                        href="/orang-tua/kondisi-anak"
                        aria-label="Kembali ke daftar laporan"
                    >
                        <ArrowLeft />
                    </Link>
                </Button>
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Laporkan kondisi anak
                    </h1>
                    <p className="text-sm text-slate-500">
                        Laporan hanya bisa dibaca guru tujuan, wali kelas, dan
                        kepala sekolah.
                    </p>
                </div>
            </div>

            {childOptions.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : (
                <form onSubmit={submit} className="mx-auto max-w-2xl space-y-4">
                    <Step number={1} title="Pilih anak">
                        <div
                            role="radiogroup"
                            aria-label="Anak"
                            className="grid gap-2 sm:grid-cols-2"
                        >
                            {childOptions.map((option) => {
                                const active =
                                    option.public_id === form.data.student;

                                return (
                                    <button
                                        key={option.public_id}
                                        type="button"
                                        role="radio"
                                        aria-checked={active}
                                        onClick={() => pickChild(option)}
                                        className={cn(
                                            'flex items-center gap-3 rounded-xl border p-3 text-left text-sm transition-colors duration-150',
                                            active
                                                ? 'border-brand-600 bg-brand-50'
                                                : 'border-slate-200 hover:border-brand-300',
                                        )}
                                    >
                                        <PersonAvatar name={option.name} />
                                        <span className="font-medium text-slate-900">
                                            {option.name}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        <InputError message={error('student')} />
                    </Step>

                    <Step number={2} title="Jenis kondisi">
                        <div
                            role="radiogroup"
                            aria-label="Jenis kondisi"
                            className="grid grid-cols-2 gap-2 sm:grid-cols-3"
                        >
                            {types.map((type) => {
                                const Icon = TYPE_ICONS[type.value];
                                const active = form.data.type === type.value;

                                return (
                                    <button
                                        key={type.value}
                                        type="button"
                                        role="radio"
                                        aria-checked={active}
                                        onClick={() =>
                                            form.setData('type', type.value)
                                        }
                                        className={cn(
                                            'flex min-w-0 flex-col items-start gap-1.5 rounded-xl border p-3 text-left transition-colors duration-150',
                                            active
                                                ? 'border-brand-600 bg-brand-50'
                                                : 'border-slate-200 hover:border-brand-300',
                                        )}
                                    >
                                        <Icon
                                            className={cn(
                                                'size-5',
                                                active
                                                    ? 'text-brand-600'
                                                    : 'text-slate-500',
                                            )}
                                        />
                                        <span className="text-sm font-medium text-slate-900">
                                            {type.label}
                                        </span>
                                        <span className="text-xs text-slate-500">
                                            {type.hint}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                        <InputError message={error('type')} />
                    </Step>

                    <Step number={3} title="Ceritakan kondisinya">
                        <div className="grid gap-2">
                            <Label
                                htmlFor="condition-description"
                                className="sr-only"
                            >
                                Deskripsi
                            </Label>
                            <Textarea
                                id="condition-description"
                                rows={4}
                                maxLength={2000}
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder="Contoh: Demam sejak semalam, sudah ke dokter. Mohon izin 2 hari."
                                aria-invalid={!!error('description')}
                            />
                            <InputError message={error('description')} />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="condition-from">
                                    Berlaku mulai
                                </Label>
                                <Input
                                    id="condition-from"
                                    type="date"
                                    value={form.data.valid_from}
                                    onChange={(event) =>
                                        form.setData(
                                            'valid_from',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!error('valid_from')}
                                />
                                <InputError message={error('valid_from')} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="condition-until">
                                    Sampai (opsional)
                                </Label>
                                <Input
                                    id="condition-until"
                                    type="date"
                                    min={form.data.valid_from}
                                    value={form.data.valid_until}
                                    onChange={(event) =>
                                        form.setData(
                                            'valid_until',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={!!error('valid_until')}
                                />
                                <InputError message={error('valid_until')} />
                            </div>
                        </div>
                    </Step>

                    <Step number={4} title="Guru tujuan">
                        {child && child.teachers.length > 0 ? (
                            <NativeSelect
                                id="condition-teacher"
                                aria-label="Guru tujuan"
                                value={form.data.target_teacher}
                                onChange={(event) =>
                                    form.setData(
                                        'target_teacher',
                                        event.target.value,
                                    )
                                }
                            >
                                {child.teachers.map((teacher) => (
                                    <option
                                        key={teacher.public_id}
                                        value={teacher.public_id}
                                    >
                                        {teacher.name} — {teacher.label}
                                    </option>
                                ))}
                            </NativeSelect>
                        ) : (
                            <p className="text-sm text-slate-500">
                                {child
                                    ? `${child.name} belum terdaftar di rombel tahun ajaran aktif.`
                                    : 'Pilih anak terlebih dahulu.'}
                            </p>
                        )}
                        <p className="text-xs text-slate-500">
                            Wali kelas selalu ikut menerima laporan.
                        </p>
                        <InputError message={error('target_teacher')} />
                    </Step>

                    <Step number={5} title="Foto (opsional)">
                        <FileUploader
                            value={form.data.photos}
                            onChange={(files) =>
                                form.setData(
                                    'photos',
                                    files.slice(0, MAX_PHOTOS),
                                )
                            }
                            accept={['image/jpeg', 'image/png', 'image/webp']}
                            multiple
                            maxFiles={MAX_PHOTOS}
                            maxSizeKb={5120}
                            hint={`Mis. surat dokter · maks ${MAX_PHOTOS} foto, 5 MB per foto`}
                            error={error('photos') ?? error('photos.0')}
                        />
                    </Step>

                    <Button
                        type="submit"
                        className="w-full"
                        size="lg"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        Kirim laporan
                    </Button>
                </form>
            )}
        </ParentLayout>
    );
}
