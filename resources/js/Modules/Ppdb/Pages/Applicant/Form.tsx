import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowRight,
    FileText,
    Send,
    Trash2,
    Upload,
} from 'lucide-react';
import { useRef, useState } from 'react';
import type { ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import { formatDate, formatFileSize } from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import ApplicantLayout from '@/Modules/Ppdb/Components/ApplicantLayout';
import { SaveIndicator } from '@/Modules/Ppdb/Components/SaveIndicator';
import type {
    ApplicantDetail,
    FormOptions,
    GuardianData,
    Requirement,
    StudentData,
} from '@/Modules/Ppdb/types';
import { useAutosave } from '@/Modules/Ppdb/use-autosave';

type Step = 'siswa' | 'orang-tua' | 'dokumen' | 'tinjau';
type Props = {
    applicant: ApplicantDetail;
    step: Step;
    editable: boolean;
    options: FormOptions;
};

const STEPS: { value: Step; label: string }[] = [
    { value: 'siswa', label: 'Data siswa' },
    { value: 'orang-tua', label: 'Orang tua' },
    { value: 'dokumen', label: 'Dokumen' },
    { value: 'tinjau', label: 'Tinjau & kirim' },
];

export default function ApplicationForm({
    applicant,
    step,
    editable,
    options,
}: Props) {
    const base = `/ppdb/akun/pendaftaran/${applicant.public_id}`;
    const go = (next: Step) =>
        router.visit(`${base}?langkah=${next}`, { preserveScroll: false });
    const index = STEPS.findIndex((s) => s.value === step);

    return (
        <ApplicantLayout>
            <Head title="Formulir PPDB" />
            <Button variant="ghost" size="sm" className="-ml-2" asChild>
                <Link href="/ppdb/akun/dashboard">
                    <ArrowLeft />
                    Dashboard
                </Link>
            </Button>
            <div>
                <h1 className="text-xl font-semibold text-slate-900">
                    {applicant.name ?? 'Formulir pendaftaran'}
                </h1>
                <p className="text-sm text-slate-500">
                    {applicant.period.name} · Jalur {applicant.track.name}
                    {applicant.registration_number &&
                        ` · ${applicant.registration_number}`}{' '}
                    · {applicant.status.label}
                </p>
            </div>

            {applicant.status.value === 'NEEDS_REVISION' &&
                applicant.revision_note && (
                    <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">
                        <strong>Catatan perbaikan:</strong>{' '}
                        {applicant.revision_note}
                    </p>
                )}

            {editable && (
                <nav
                    aria-label="Langkah formulir"
                    className="grid grid-cols-4 gap-1 rounded-xl bg-white p-1 shadow-sm ring-1 ring-slate-200"
                >
                    {STEPS.map((item, i) => (
                        <button
                            key={item.value}
                            type="button"
                            onClick={() => go(item.value)}
                            aria-current={
                                item.value === step ? 'step' : undefined
                            }
                            className={cn(
                                'rounded-lg px-1 py-2 text-center text-[11px] font-medium transition-colors duration-150 sm:text-sm',
                                item.value === step
                                    ? 'bg-brand-600 text-white'
                                    : i < index
                                      ? 'text-brand-700 hover:bg-brand-50'
                                      : 'text-slate-500 hover:bg-slate-50',
                            )}
                        >
                            <span className="block text-[10px] opacity-70 sm:inline sm:text-xs">
                                {i + 1}.{' '}
                            </span>
                            {item.label}
                        </button>
                    ))}
                </nav>
            )}

            {step === 'siswa' && (
                <StudentStep
                    base={base}
                    student={applicant.student}
                    options={options}
                    onNext={() => go('orang-tua')}
                />
            )}
            {step === 'orang-tua' && (
                <GuardiansStep
                    base={base}
                    guardians={applicant.guardians}
                    options={options}
                    onBack={() => go('siswa')}
                    onNext={() => go('dokumen')}
                />
            )}
            {step === 'dokumen' && (
                <DocumentsStep
                    base={base}
                    requirements={applicant.requirements}
                    onBack={() => go('orang-tua')}
                    onNext={() => go('tinjau')}
                />
            )}
            {step === 'tinjau' && (
                <ReviewStep
                    base={base}
                    applicant={applicant}
                    options={options}
                    editable={editable}
                    onBack={() => go('dokumen')}
                />
            )}
        </ApplicantLayout>
    );
}

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
        <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
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

function Field({
    id,
    label,
    error,
    hint,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <div className="grid content-start gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

function Nav({
    onBack,
    onNext,
    nextLabel = 'Lanjut',
    busy = false,
}: {
    onBack?: () => void;
    onNext: () => void;
    nextLabel?: string;
    busy?: boolean;
}) {
    return (
        <div className="flex justify-between gap-2">
            {onBack ? (
                <Button type="button" variant="outline" onClick={onBack}>
                    <ArrowLeft />
                    Kembali
                </Button>
            ) : (
                <span />
            )}
            <Button type="button" onClick={onNext} disabled={busy}>
                {busy && <Spinner />}
                {nextLabel}
                <ArrowRight />
            </Button>
        </div>
    );
}

function StudentStep({
    base,
    student,
    options,
    onNext,
}: {
    base: string;
    student: StudentData;
    options: FormOptions;
    onNext: () => void;
}) {
    const [data, setData] = useState<Record<keyof StudentData, string>>(
        () =>
            Object.fromEntries(
                Object.entries(student).map(([k, v]) => [k, v ?? '']),
            ) as Record<keyof StudentData, string>,
    );
    const { state, savedAt, errors, saveNow } = useAutosave(
        base,
        { student: data },
        true,
    );
    const set = (key: keyof StudentData, value: string) =>
        setData((d) => ({ ...d, [key]: value }));
    const err = (key: keyof StudentData) => errors[`student.${key}`];

    const next = async () => {
        if (await saveNow(true)) {
            onNext();
        }
    };

    return (
        <Card
            title="Data calon siswa"
            actions={<SaveIndicator state={state} savedAt={savedAt} />}
        >
            <Field
                id="st-name"
                label="Nama lengkap (sesuai akta)"
                error={err('name')}
            >
                <Input
                    id="st-name"
                    maxLength={150}
                    value={data.name}
                    onChange={(e) => set('name', e.target.value)}
                    aria-invalid={!!err('name')}
                />
            </Field>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field
                    id="st-nik"
                    label="NIK"
                    error={err('nik')}
                    hint="16 digit, lihat kartu keluarga"
                >
                    <Input
                        id="st-nik"
                        inputMode="numeric"
                        maxLength={16}
                        value={data.nik}
                        onChange={(e) =>
                            set('nik', e.target.value.replace(/\D/g, ''))
                        }
                        aria-invalid={!!err('nik')}
                    />
                </Field>
                <Field id="st-nisn" label="NISN (opsional)" error={err('nisn')}>
                    <Input
                        id="st-nisn"
                        inputMode="numeric"
                        maxLength={10}
                        value={data.nisn}
                        onChange={(e) =>
                            set('nisn', e.target.value.replace(/\D/g, ''))
                        }
                    />
                </Field>
                <Field
                    id="st-gender"
                    label="Jenis kelamin"
                    error={err('gender')}
                >
                    <NativeSelect
                        id="st-gender"
                        value={data.gender}
                        onChange={(e) => set('gender', e.target.value)}
                    >
                        <option value="">Pilih…</option>
                        {options.genders.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <Field id="st-religion" label="Agama" error={err('religion')}>
                    <NativeSelect
                        id="st-religion"
                        value={data.religion}
                        onChange={(e) => set('religion', e.target.value)}
                    >
                        <option value="">Pilih…</option>
                        {options.religions.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <Field
                    id="st-birth-place"
                    label="Tempat lahir"
                    error={err('birth_place')}
                >
                    <Input
                        id="st-birth-place"
                        maxLength={100}
                        value={data.birth_place}
                        onChange={(e) => set('birth_place', e.target.value)}
                    />
                </Field>
                <Field
                    id="st-birth-date"
                    label="Tanggal lahir"
                    error={err('birth_date')}
                >
                    <Input
                        id="st-birth-date"
                        type="date"
                        value={data.birth_date}
                        onChange={(e) => set('birth_date', e.target.value)}
                    />
                </Field>
            </div>
            <Field id="st-address" label="Alamat" error={err('address')}>
                <Textarea
                    id="st-address"
                    rows={3}
                    maxLength={500}
                    value={data.address}
                    onChange={(e) => set('address', e.target.value)}
                />
            </Field>
            <Field
                id="st-school"
                label="Asal sekolah (opsional)"
                error={err('previous_school')}
            >
                <Input
                    id="st-school"
                    maxLength={150}
                    value={data.previous_school}
                    onChange={(e) => set('previous_school', e.target.value)}
                    placeholder="SD Negeri 1 …"
                />
            </Field>
            <Nav onNext={next} busy={state === 'saving'} />
        </Card>
    );
}

type GuardianRow = Record<keyof Omit<GuardianData, 'is_primary'>, string> & {
    is_primary: boolean;
};

function GuardiansStep({
    base,
    guardians,
    options,
    onBack,
    onNext,
}: {
    base: string;
    guardians: GuardianData[];
    options: FormOptions;
    onBack: () => void;
    onNext: () => void;
}) {
    const [rows, setRows] = useState<GuardianRow[]>(() =>
        guardians.length > 0
            ? guardians.map((g) => ({
                  relationship: g.relationship,
                  name: g.name,
                  nik: g.nik ?? '',
                  occupation: g.occupation ?? '',
                  phone: g.phone ?? '',
                  email: g.email ?? '',
                  is_primary: g.is_primary,
              }))
            : [
                  {
                      relationship: options.relationships[0]?.value ?? 'AYAH',
                      name: '',
                      nik: '',
                      occupation: '',
                      phone: '',
                      email: '',
                      is_primary: true,
                  },
              ],
    );
    const { state, savedAt, errors, saveNow } = useAutosave(
        base,
        { guardians: rows },
        true,
    );
    const used = rows.map((r) => r.relationship);
    const available = options.relationships.filter(
        (o) => !used.includes(o.value),
    );
    const set = (i: number, patch: Partial<GuardianRow>) =>
        setRows((current) =>
            current.map((r, j) =>
                j === i
                    ? { ...r, ...patch }
                    : patch.is_primary
                      ? { ...r, is_primary: false }
                      : r,
            ),
        );

    const next = async () => {
        if (await saveNow(true)) {
            onNext();
        }
    };

    return (
        <Card
            title="Data orang tua / wali"
            actions={<SaveIndicator state={state} savedAt={savedAt} />}
        >
            <p className="text-sm text-slate-500">
                Isi minimal satu. Tandai kontak utama yang akan dihubungi
                sekolah.
            </p>
            <InputError message={errors.guardians} />
            {rows.map((row, i) => {
                const e = (key: string) => errors[`guardians.${i}.${key}`];

                return (
                    <fieldset
                        key={row.relationship}
                        className="space-y-3 rounded-xl border border-slate-200 p-3 sm:p-4"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <legend className="text-sm font-semibold text-slate-900">
                                {
                                    options.relationships.find(
                                        (o) => o.value === row.relationship,
                                    )?.label
                                }
                            </legend>
                            <div className="flex items-center gap-3">
                                <label className="flex items-center gap-1.5 text-xs text-slate-600">
                                    <input
                                        type="radio"
                                        name="primary"
                                        className="size-4 accent-brand-600"
                                        checked={row.is_primary}
                                        onChange={() =>
                                            set(i, { is_primary: true })
                                        }
                                    />
                                    Kontak utama
                                </label>
                                {rows.length > 1 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="size-8 text-slate-400"
                                        onClick={() =>
                                            setRows((c) =>
                                                c.filter((_, j) => j !== i),
                                            )
                                        }
                                        aria-label="Hapus"
                                    >
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Field
                                id={`g-${i}-name`}
                                label="Nama lengkap"
                                error={e('name')}
                            >
                                <Input
                                    id={`g-${i}-name`}
                                    maxLength={150}
                                    value={row.name}
                                    onChange={(ev) =>
                                        set(i, { name: ev.target.value })
                                    }
                                />
                            </Field>
                            <Field
                                id={`g-${i}-phone`}
                                label="Telepon/WhatsApp"
                                error={e('phone')}
                            >
                                <Input
                                    id={`g-${i}-phone`}
                                    inputMode="tel"
                                    maxLength={20}
                                    value={row.phone}
                                    onChange={(ev) =>
                                        set(i, { phone: ev.target.value })
                                    }
                                    placeholder="08…"
                                />
                            </Field>
                            <Field
                                id={`g-${i}-nik`}
                                label="NIK (opsional)"
                                error={e('nik')}
                            >
                                <Input
                                    id={`g-${i}-nik`}
                                    inputMode="numeric"
                                    maxLength={16}
                                    value={row.nik}
                                    onChange={(ev) =>
                                        set(i, {
                                            nik: ev.target.value.replace(
                                                /\D/g,
                                                '',
                                            ),
                                        })
                                    }
                                />
                            </Field>
                            <Field
                                id={`g-${i}-job`}
                                label="Pekerjaan (opsional)"
                                error={e('occupation')}
                            >
                                <Input
                                    id={`g-${i}-job`}
                                    maxLength={100}
                                    value={row.occupation}
                                    onChange={(ev) =>
                                        set(i, { occupation: ev.target.value })
                                    }
                                />
                            </Field>
                            <Field
                                id={`g-${i}-email`}
                                label="Email (opsional)"
                                error={e('email')}
                            >
                                <Input
                                    id={`g-${i}-email`}
                                    type="email"
                                    maxLength={150}
                                    value={row.email}
                                    onChange={(ev) =>
                                        set(i, { email: ev.target.value })
                                    }
                                />
                            </Field>
                        </div>
                    </fieldset>
                );
            })}
            {available.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {available.map((o) => (
                        <Button
                            key={o.value}
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                setRows((c) => [
                                    ...c,
                                    {
                                        relationship: o.value,
                                        name: '',
                                        nik: '',
                                        occupation: '',
                                        phone: '',
                                        email: '',
                                        is_primary: false,
                                    },
                                ])
                            }
                        >
                            + Tambah {o.label.toLowerCase()}
                        </Button>
                    ))}
                </div>
            )}
            <Nav onBack={onBack} onNext={next} busy={state === 'saving'} />
        </Card>
    );
}

function DocumentsStep({
    base,
    requirements,
    onBack,
    onNext,
}: {
    base: string;
    requirements: Requirement[];
    onBack: () => void;
    onNext: () => void;
}) {
    return (
        <Card title="Dokumen persyaratan">
            <p className="text-sm text-slate-500">
                Foto yang jelas atau PDF, maks 5 MB per dokumen.
            </p>
            {requirements.length === 0 ? (
                <p className="text-sm text-slate-500">
                    Jalur ini tidak mensyaratkan dokumen.
                </p>
            ) : (
                <ul className="space-y-3">
                    {requirements.map((req) => (
                        <DocumentRow
                            key={req.public_id}
                            base={base}
                            requirement={req}
                        />
                    ))}
                </ul>
            )}
            <Nav onBack={onBack} onNext={onNext} />
        </Card>
    );
}

function DocumentRow({
    base,
    requirement,
}: {
    base: string;
    requirement: Requirement;
}) {
    const input = useRef<HTMLInputElement>(null);
    const [busy, setBusy] = useState(false);
    const url = `${base}/dokumen/${requirement.public_id}`;
    const doc = requirement.document;

    const upload = (file: File | undefined) => {
        if (!file) {
            return;
        }

        setBusy(true);
        router.post(
            url,
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: toastFirstError,
                onFinish: () => setBusy(false),
            },
        );
    };

    return (
        <li className="space-y-2 rounded-xl border border-slate-200 p-3">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="min-w-0">
                    <p className="text-sm font-medium text-slate-900">
                        {requirement.name}{' '}
                        {requirement.is_required ? (
                            <span className="text-rose-600">*</span>
                        ) : (
                            <span className="text-xs font-normal text-slate-500">
                                (opsional)
                            </span>
                        )}
                    </p>
                    {requirement.description && (
                        <p className="text-xs text-slate-500">
                            {requirement.description}
                        </p>
                    )}
                </div>
                {doc && (
                    <StatusBadge
                        variant={
                            doc.status.value === 'VALID'
                                ? 'success'
                                : doc.status.value === 'INVALID'
                                  ? 'danger'
                                  : 'neutral'
                        }
                        dot
                    >
                        {doc.status.label}
                    </StatusBadge>
                )}
            </div>
            {doc?.note && (
                <p className="rounded-lg bg-rose-50 px-2 py-1 text-xs text-rose-700">
                    {doc.note}
                </p>
            )}
            <div className="flex flex-wrap items-center gap-2">
                {doc && (
                    <a
                        href={doc.file.url}
                        target="_blank"
                        rel="noreferrer"
                        className="flex min-w-0 items-center gap-1 text-sm text-brand-600 hover:text-brand-700"
                    >
                        <FileText className="size-4 shrink-0" />
                        <span className="truncate">{doc.file.name}</span>
                        <span className="shrink-0 text-xs text-slate-400">
                            ({formatFileSize(doc.file.size)})
                        </span>
                    </a>
                )}
                <input
                    ref={input}
                    type="file"
                    className="hidden"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    onChange={(e) => upload(e.target.files?.[0])}
                />
                <Button
                    type="button"
                    size="sm"
                    variant={doc ? 'outline' : 'default'}
                    onClick={() => input.current?.click()}
                    disabled={busy}
                >
                    {busy ? <Spinner /> : <Upload />}
                    {doc ? 'Ganti' : 'Unggah'}
                </Button>
                {doc && (
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        className="text-slate-500"
                        onClick={() =>
                            router.delete(url, { preserveScroll: true })
                        }
                    >
                        Hapus
                    </Button>
                )}
            </div>
        </li>
    );
}

function ReviewStep({
    base,
    applicant,
    options,
    editable,
    onBack,
}: {
    base: string;
    applicant: ApplicantDetail;
    options: FormOptions;
    editable: boolean;
    onBack: () => void;
}) {
    const [agreed, setAgreed] = useState(false);
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const label = (
        list: { value: string; label: string }[],
        value: string | null,
    ) => list.find((o) => o.value === value)?.label ?? '—';
    const s = applicant.student;

    const submit = () => {
        setBusy(true);
        router.post(
            `${base}/kirim`,
            {},
            { onError: (e) => setErrors(e), onFinish: () => setBusy(false) },
        );
    };

    return (
        <>
            <Card title="Data calon siswa">
                <dl className="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                    <Item label="Nama">{s.name}</Item>
                    <Item label="NIK / NISN">{`${s.nik ?? '—'} / ${s.nisn ?? '—'}`}</Item>
                    <Item label="Jenis kelamin">
                        {label(options.genders, s.gender)}
                    </Item>
                    <Item label="Agama">
                        {label(options.religions, s.religion)}
                    </Item>
                    <Item label="Tempat, tanggal lahir">{`${s.birth_place ?? '—'}, ${s.birth_date ? formatDate(s.birth_date, 'd MMMM yyyy') : '—'}`}</Item>
                    <Item label="Asal sekolah">{s.previous_school}</Item>
                    <Item label="Alamat" wide>
                        {s.address}
                    </Item>
                </dl>
            </Card>
            <Card title="Orang tua / wali">
                {applicant.guardians.length === 0 ? (
                    <p className="text-sm text-slate-500">Belum diisi.</p>
                ) : (
                    <ul className="space-y-2 text-sm">
                        {applicant.guardians.map((g) => (
                            <li key={g.relationship}>
                                <span className="font-medium text-slate-900">
                                    {label(
                                        options.relationships,
                                        g.relationship,
                                    )}
                                    {g.is_primary && ' (kontak utama)'}:
                                </span>{' '}
                                {g.name} · {g.phone ?? '—'}
                                {g.occupation && ` · ${g.occupation}`}
                            </li>
                        ))}
                    </ul>
                )}
            </Card>
            <Card title="Dokumen">
                <ul className="space-y-1 text-sm">
                    {applicant.requirements.map((r) => (
                        <li
                            key={r.public_id}
                            className="flex justify-between gap-3"
                        >
                            <span className="text-slate-700">{r.name}</span>
                            <span
                                className={
                                    r.document
                                        ? 'text-emerald-700'
                                        : r.is_required
                                          ? 'text-rose-600'
                                          : 'text-slate-400'
                                }
                            >
                                {r.document
                                    ? r.document.status.label
                                    : r.is_required
                                      ? 'Belum diunggah'
                                      : '—'}
                            </span>
                        </li>
                    ))}
                </ul>
            </Card>
            {editable && (
                <Card title="Kirim pendaftaran">
                    {Object.keys(errors).length > 0 && (
                        <ul className="list-inside list-disc rounded-xl bg-rose-50 p-3 text-sm text-rose-700">
                            {Object.values(errors).map((message) => (
                                <li key={message}>{message}</li>
                            ))}
                        </ul>
                    )}
                    <label className="flex items-start gap-2 text-sm text-slate-700">
                        <Checkbox
                            checked={agreed}
                            onCheckedChange={(c) => setAgreed(c === true)}
                            className="mt-0.5"
                        />
                        Saya menyatakan data yang diisi benar dan dapat
                        dipertanggungjawabkan.
                    </label>
                    <div className="flex justify-between gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={onBack}
                        >
                            <ArrowLeft />
                            Kembali
                        </Button>
                        <Button
                            type="button"
                            onClick={submit}
                            disabled={!agreed || busy}
                        >
                            {busy ? <Spinner /> : <Send />}
                            Kirim pendaftaran
                        </Button>
                    </div>
                </Card>
            )}
        </>
    );
}

function Item({
    label,
    children,
    wide = false,
}: {
    label: string;
    children: ReactNode;
    wide?: boolean;
}) {
    return (
        <div className={wide ? 'sm:col-span-2' : undefined}>
            <dt className="text-xs text-slate-500">{label}</dt>
            <dd className="break-words text-slate-900">{children || '—'}</dd>
        </div>
    );
}
