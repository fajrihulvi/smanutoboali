import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardCheck,
    Eye,
    Send,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { Lightbox } from '@/Components/shared/Lightbox';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDateTime } from '@/lib/format';
import {
    ConditionStatusBadge,
    TypeIcon,
    validRange,
} from '@/Modules/ChildCondition/Components/ConditionBits';
import type { ConditionDetail } from '@/Modules/ChildCondition/types';

type Props = {
    base: string;
    condition: ConditionDetail;
};

export default function ConditionShow({ base, condition }: Props) {
    const { auth } = usePage().props;
    const guardian = auth.activeRole === 'guardian';
    const [photo, setPhoto] = useState<number | null>(null);
    const [confirm, setConfirm] = useState<'close' | 'withdraw' | null>(null);
    const [busy, setBusy] = useState(false);
    const reply = useForm({ body: '' });

    const url = `${base}/${condition.public_id}`;

    const send = (event: FormEvent) => {
        event.preventDefault();
        reply.post(`${url}/tanggapan`, {
            preserveScroll: true,
            onSuccess: () => reply.reset(),
        });
    };

    const act = (action: 'tinjau' | 'selesai' | 'withdraw') => {
        setBusy(true);
        const options = {
            preserveScroll: true,
            onFinish: () => {
                setBusy(false);
                setConfirm(null);
            },
        };

        if (action === 'withdraw') {
            router.delete(url, options);
        } else {
            router.post(`${url}/${action}`, {}, options);
        }
    };

    const content = (
        <>
            <Head title={`Kondisi ${condition.student.name}`} />

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="min-w-0 space-y-5 lg:col-span-2">
                    <section className="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="flex items-start gap-3">
                            <TypeIcon type={condition.type.value} />
                            <div className="min-w-0 flex-1">
                                <p className="text-base font-semibold text-slate-900">
                                    {condition.type.label}
                                </p>
                                <p className="text-sm text-slate-500">
                                    {condition.student.name} · berlaku{' '}
                                    {validRange(condition)}
                                </p>
                            </div>
                            <ConditionStatusBadge status={condition.status} />
                        </div>
                        <p className="text-sm break-words whitespace-pre-wrap text-slate-700">
                            {condition.description}
                        </p>
                        {condition.photos.length > 0 && (
                            <ul className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                {condition.photos.map((item, index) => (
                                    <li key={item.public_id}>
                                        <button
                                            type="button"
                                            onClick={() => setPhoto(index)}
                                            className="block w-full overflow-hidden rounded-lg border border-slate-200"
                                            aria-label={`Lihat ${item.name}`}
                                        >
                                            <img
                                                src={item.url}
                                                alt={item.name}
                                                className="aspect-square w-full object-cover"
                                            />
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <dl className="grid gap-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2">
                            <Meta label="Pelapor">
                                {condition.reporter ?? '—'}
                            </Meta>
                            <Meta label="Guru tujuan">
                                {condition.target_teacher ?? '—'}
                            </Meta>
                            <Meta label="Dikirim">
                                {formatDateTime(condition.created_at)}
                            </Meta>
                            {condition.attendance_suggestion && !guardian && (
                                <Meta label="Usulan presensi">
                                    {condition.attendance_suggestion} (terapkan
                                    di halaman presensi)
                                </Meta>
                            )}
                        </dl>
                    </section>

                    <section className="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-sm font-semibold text-slate-900">
                            Tanggapan guru
                        </h2>
                        {condition.responses.length === 0 ? (
                            <p className="text-sm text-slate-500">
                                Belum ada tanggapan.
                            </p>
                        ) : (
                            <ol className="space-y-3">
                                {condition.responses.map((response, index) => (
                                    <li
                                        key={index}
                                        className="rounded-xl bg-brand-50 p-3"
                                    >
                                        <p className="text-xs font-medium text-brand-700">
                                            {response.author} ·{' '}
                                            {formatDateTime(
                                                response.created_at,
                                            )}
                                        </p>
                                        <p className="mt-1 text-sm break-words whitespace-pre-wrap text-slate-800">
                                            {response.body}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        )}

                        {condition.can.respond && (
                            <form onSubmit={send} className="space-y-2 pt-2">
                                <Textarea
                                    rows={3}
                                    maxLength={2000}
                                    value={reply.data.body}
                                    onChange={(event) =>
                                        reply.setData(
                                            'body',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Tulis tanggapan untuk orang tua…"
                                    aria-label="Tanggapan"
                                    aria-invalid={!!reply.errors.body}
                                />
                                <InputError message={reply.errors.body} />
                                <div className="flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={
                                            reply.processing ||
                                            reply.data.body.trim() === ''
                                        }
                                    >
                                        {reply.processing ? (
                                            <Spinner />
                                        ) : (
                                            <Send />
                                        )}
                                        Kirim tanggapan
                                    </Button>
                                </div>
                            </form>
                        )}
                    </section>
                </div>

                <aside className="min-w-0 space-y-5">
                    {(condition.can.respond || condition.can.withdraw) && (
                        <section className="space-y-2 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 className="text-sm font-semibold text-slate-900">
                                Tindakan
                            </h2>
                            {condition.can.review && (
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    disabled={busy}
                                    onClick={() => act('tinjau')}
                                >
                                    <Eye />
                                    Tandai sudah ditinjau
                                </Button>
                            )}
                            {condition.can.respond && (
                                <Button
                                    variant="outline"
                                    className="w-full justify-start"
                                    disabled={busy}
                                    onClick={() => setConfirm('close')}
                                >
                                    <CheckCircle2 />
                                    Tandai selesai
                                </Button>
                            )}
                            {condition.can.withdraw && (
                                <Button
                                    variant="outline"
                                    className="w-full justify-start text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                    disabled={busy}
                                    onClick={() => setConfirm('withdraw')}
                                >
                                    <Trash2 />
                                    Tarik laporan
                                </Button>
                            )}
                        </section>
                    )}

                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <ClipboardCheck className="size-4 text-brand-600" />
                            Riwayat status
                        </h2>
                        <ol className="relative space-y-3 border-l border-slate-200 pl-4">
                            {condition.timeline.map((step) => (
                                <li key={step.label} className="relative">
                                    <span className="absolute top-1.5 -left-[21px] size-2.5 rounded-full bg-brand-500 ring-2 ring-white" />
                                    <p className="text-sm font-medium text-slate-800">
                                        {step.label}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {formatDateTime(step.at)}
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </section>
                </aside>
            </div>

            <Lightbox
                images={condition.photos.map((p) => ({
                    url: p.url,
                    name: p.name,
                }))}
                index={photo}
                onIndexChange={setPhoto}
                title={`Foto laporan ${condition.student.name}`}
            />

            <ConfirmDialog
                open={confirm !== null}
                onOpenChange={(open) => !open && setConfirm(null)}
                title={
                    confirm === 'withdraw'
                        ? 'Tarik laporan ini?'
                        : 'Tandai laporan selesai?'
                }
                description={
                    confirm === 'withdraw'
                        ? 'Laporan dan fotonya dihapus. Guru belum membukanya.'
                        : 'Orang tua akan diberi tahu. Laporan yang selesai tidak bisa ditanggapi lagi.'
                }
                confirmLabel={
                    confirm === 'withdraw' ? 'Tarik' : 'Tandai selesai'
                }
                variant={confirm === 'withdraw' ? 'danger' : 'default'}
                processing={busy}
                onConfirm={() =>
                    act(confirm === 'withdraw' ? 'withdraw' : 'selesai')
                }
            />
        </>
    );

    if (guardian) {
        return (
            <ParentLayout>
                <div className="flex items-center gap-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link
                            href={base}
                            aria-label="Kembali ke daftar laporan"
                        >
                            <ArrowLeft />
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Laporan kondisi {condition.student.name}
                    </h1>
                </div>
                {content}
            </ParentLayout>
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Kondisi Anak', href: base },
                { title: condition.student.name, href: '#' },
            ]}
        >
            <PageHeader
                title={`Kondisi ${condition.student.name}`}
                description="Data sensitif — hanya untuk guru tujuan, wali kelas, dan kepala sekolah."
            />
            {content}
        </AppLayout>
    );
}

function Meta({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div>
            <dt className="text-xs text-slate-500">{label}</dt>
            <dd className="font-medium text-slate-800">{children}</dd>
        </div>
    );
}
