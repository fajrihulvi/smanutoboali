import { Head, Link, useForm, usePoll } from '@inertiajs/react';
import { ChevronDown, Download, FileSpreadsheet, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { FormEvent } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { formatDateTime, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { StudentImportRow } from '@/Modules/People/types';
import type { Paginated, StatusVariant } from '@/types';

type Props = {
    imports: Paginated<StudentImportRow>;
};

const BASE = '/operator/master-data/siswa';

const statusVariant: Record<string, StatusVariant> = {
    PENDING: 'neutral',
    PROCESSING: 'info',
    COMPLETED: 'success',
    FAILED: 'danger',
};

function ImportItem({ item }: { item: StudentImportRow }) {
    const [open, setOpen] = useState(false);

    return (
        <li className="rounded-xl border border-slate-200 p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="flex items-center gap-2 font-medium break-all text-slate-900">
                        <FileSpreadsheet className="size-4 shrink-0 text-brand-600" />
                        {item.original_name}
                    </p>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {formatDateTime(item.created_at)}
                        {item.created_by && ` · ${item.created_by}`}
                    </p>
                </div>
                <StatusBadge
                    variant={statusVariant[item.status.value] ?? 'neutral'}
                    dot
                >
                    {!item.finished && <Spinner className="size-3" />}
                    {item.status.label}
                </StatusBadge>
            </div>

            {item.total_rows > 0 && (
                <dl className="mt-3 grid grid-cols-3 gap-2 text-center text-sm">
                    <div className="rounded-lg bg-slate-50 py-2">
                        <dt className="text-xs text-slate-500">Baris</dt>
                        <dd className="font-semibold tabular-nums">
                            {formatNumber(item.total_rows)}
                        </dd>
                    </div>
                    <div className="rounded-lg bg-emerald-50 py-2">
                        <dt className="text-xs text-emerald-700">Berhasil</dt>
                        <dd className="font-semibold text-emerald-700 tabular-nums">
                            {formatNumber(item.success_rows)}
                        </dd>
                    </div>
                    <div className="rounded-lg bg-rose-50 py-2">
                        <dt className="text-xs text-rose-700">Gagal</dt>
                        <dd className="font-semibold text-rose-700 tabular-nums">
                            {formatNumber(item.failed_rows)}
                        </dd>
                    </div>
                </dl>
            )}

            {item.failure_message && (
                <p className="mt-3 text-sm text-rose-600">
                    {item.failure_message}
                </p>
            )}

            {item.errors.length > 0 && (
                <div className="mt-3">
                    <button
                        type="button"
                        onClick={() => setOpen((value) => !value)}
                        className="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700"
                        aria-expanded={open}
                    >
                        <ChevronDown
                            className={cn(
                                'size-4 transition-transform duration-150',
                                open && 'rotate-180',
                            )}
                        />
                        {open ? 'Sembunyikan' : 'Lihat'} {item.errors.length}{' '}
                        baris gagal
                    </button>
                    {open && (
                        <ul className="mt-2 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 text-sm">
                            {item.errors.map((error) => (
                                <li
                                    key={error.row}
                                    className="flex gap-3 px-3 py-2"
                                >
                                    <span className="w-16 shrink-0 font-medium text-slate-500 tabular-nums">
                                        Baris {error.row}
                                    </span>
                                    <span className="text-slate-700">
                                        {error.messages.join(' ')}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}
        </li>
    );
}

export default function StudentImport({ imports }: Props) {
    const form = useForm({ file: null as File | null });
    const hasRunning = imports.data.some((item) => !item.finished);

    // Perbarui status tiap 5 detik selama masih ada import yang diproses.
    const { start, stop } = usePoll(
        5000,
        { only: ['imports'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (hasRunning) {
            start();
        } else {
            stop();
        }

        return () => {
            stop();
        };
    }, [hasRunning, start, stop]);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`${BASE}/impor`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Siswa', href: BASE },
                { title: 'Impor Excel', href: `${BASE}/impor` },
            ]}
        >
            <Head title="Impor Siswa" />

            <PageHeader
                title="Impor siswa & orang tua"
                description="Unggah banyak siswa sekaligus dari Excel. File diproses di latar belakang; baris yang gagal dilaporkan."
                actions={
                    <Button variant="outline" asChild>
                        <Link href={BASE}>Kembali ke data siswa</Link>
                    </Button>
                }
            />

            <div className="grid gap-6 lg:grid-cols-5">
                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                    <h2 className="text-base font-semibold text-slate-900">
                        1. Siapkan file
                    </h2>
                    <ul className="mt-3 list-disc space-y-1 pl-5 text-sm text-slate-600">
                        <li>Unduh template dan isi satu siswa per baris.</li>
                        <li>
                            Wajib: <b>nis</b>, <b>nama</b>, <b>jenis_kelamin</b>{' '}
                            (L/P).
                        </li>
                        <li>Tanggal: YYYY-MM-DD atau DD/MM/YYYY.</li>
                        <li>
                            Orang tua (ayah/ibu/wali) opsional; data yang sama
                            dipakai ulang untuk kakak-adik.
                        </li>
                        <li>
                            NIS yang sudah terdaftar dilewati dan dilaporkan.
                        </li>
                    </ul>
                    <Button variant="secondary" className="mt-4" asChild>
                        <a href={`${BASE}/impor/template`}>
                            <Download />
                            Unduh template (.xlsx)
                        </a>
                    </Button>

                    <h2 className="mt-8 text-base font-semibold text-slate-900">
                        2. Unggah file
                    </h2>
                    <form onSubmit={submit} className="mt-3 space-y-4">
                        <FileUploader
                            value={form.data.file ? [form.data.file] : []}
                            onChange={(files) =>
                                form.setData('file', files[0] ?? null)
                            }
                            maxSizeKb={10240}
                            hint=".xlsx atau .csv · maks 10 MB"
                            error={form.errors.file}
                        />
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={!form.data.file || form.processing}
                        >
                            {form.processing ? <Spinner /> : <Upload />}
                            Mulai impor
                        </Button>
                    </form>
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-3">
                    <div className="border-b border-slate-200 p-6 pb-4">
                        <h2 className="text-base font-semibold text-slate-900">
                            Riwayat impor
                        </h2>
                    </div>
                    {imports.data.length === 0 ? (
                        <EmptyState
                            icon={FileSpreadsheet}
                            title="Belum ada impor"
                            description="Hasil impor akan muncul di sini beserta baris yang gagal."
                        />
                    ) : (
                        <>
                            <ul className="space-y-3 p-6">
                                {imports.data.map((item) => (
                                    <ImportItem
                                        key={item.public_id}
                                        item={item}
                                    />
                                ))}
                            </ul>
                            <Pagination
                                paginated={imports}
                                only={['imports']}
                            />
                        </>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
