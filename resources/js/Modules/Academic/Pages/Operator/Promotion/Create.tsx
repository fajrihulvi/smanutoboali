import { Head, router, useForm } from '@inertiajs/react';
import { ArrowRight, GraduationCap, Info, Shapes } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/Components/input-error';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatCard } from '@/Components/shared/StatCard';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import AppLayout from '@/Layouts/AppLayout';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { YearOption } from '@/Modules/Academic/types';

type PlanRow = {
    source: string;
    grade: number;
    students: number;
    action: 'promote' | 'graduate';
    target: string | null;
    target_grade: number | null;
    target_exists: boolean;
};

type Plan = {
    rows: PlanRow[];
    new_empty: { name: string; grade: number; exists: boolean }[];
    promote: number;
    graduate: number;
};

type Props = {
    years: YearOption[];
    from: string | null;
    to: string | null;
    plan: Plan | null;
    error: string | null;
};

const BASE = '/operator/master-data/kelas';

export default function PromotionCreate({
    years,
    from,
    to,
    plan,
    error,
}: Props) {
    const [confirming, setConfirming] = useState(false);
    const form = useForm({
        from: from ?? '',
        to: to ?? '',
        graduate_final_grade: true,
    });

    const choose = (key: 'dari' | 'ke', value: string) => {
        router.get(
            `${BASE}/naik-kelas`,
            {
                dari: key === 'dari' ? value : (from ?? undefined),
                ke: key === 'ke' ? value : (to ?? undefined),
            },
            { preserveScroll: true },
        );
    };

    const yearLabel = (publicId: string | null) =>
        years.find((year) => year.value === publicId)?.label ?? '';

    const submit = () => {
        form.post(`${BASE}/naik-kelas`, {
            onFinish: () => setConfirming(false),
        });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Kelas', href: BASE },
                { title: 'Naik Kelas', href: `${BASE}/naik-kelas` },
            ]}
        >
            <Head title="Naik Kelas" />

            <PageHeader
                title="Naik Kelas"
                description="Salin struktur rombel ke tahun ajaran berikutnya dan pindahkan siswa aktif ke tingkat di atasnya."
            />

            <section className="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="promotion-from">
                            Dari tahun ajaran
                        </Label>
                        <NativeSelect
                            id="promotion-from"
                            value={from ?? ''}
                            onChange={(event) =>
                                choose('dari', event.target.value)
                            }
                        >
                            <option value="">— Pilih —</option>
                            {years.map((year) => (
                                <option key={year.value} value={year.value}>
                                    TA {year.label}
                                    {year.is_active ? ' (aktif)' : ''}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <ArrowRight className="hidden size-5 shrink-0 text-slate-400 sm:mb-2.5 sm:block" />
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="promotion-to">Ke tahun ajaran</Label>
                        <NativeSelect
                            id="promotion-to"
                            value={to ?? ''}
                            onChange={(event) =>
                                choose('ke', event.target.value)
                            }
                        >
                            <option value="">— Pilih —</option>
                            {years
                                .filter((year) => year.value !== from)
                                .map((year) => (
                                    <option key={year.value} value={year.value}>
                                        TA {year.label}
                                        {year.is_active ? ' (aktif)' : ''}
                                    </option>
                                ))}
                        </NativeSelect>
                    </div>
                </div>
                <InputError
                    className="mt-2"
                    message={form.errors.from ?? form.errors.to}
                />
            </section>

            {!plan ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Info}
                        title={error ?? 'Pilih tahun ajaran asal dan tujuan'}
                        description="Pratinjau rombel dan jumlah siswa akan tampil di sini sebelum dieksekusi."
                    />
                </div>
            ) : plan.rows.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Shapes}
                        title="Tidak ada rombel"
                        description={`TA ${yearLabel(from)} belum memiliki rombel.`}
                    />
                </div>
            ) : (
                <>
                    <div className="mb-6 grid gap-4 sm:grid-cols-3">
                        <StatCard
                            label="Siswa naik kelas"
                            value={plan.promote}
                            icon={ArrowRight}
                        />
                        <StatCard
                            label="Siswa tingkat akhir"
                            value={plan.graduate}
                            icon={GraduationCap}
                        />
                        <StatCard
                            label="Rombel baru"
                            value={
                                plan.rows.filter(
                                    (row) =>
                                        row.action === 'promote' &&
                                        !row.target_exists,
                                ).length +
                                plan.new_empty.filter((item) => !item.exists)
                                    .length
                            }
                            icon={Shapes}
                        />
                    </div>

                    <section className="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="relative overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold text-slate-600">
                                    <tr>
                                        <th className="px-4 py-3">
                                            Rombel {yearLabel(from)}
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Siswa aktif
                                        </th>
                                        <th className="px-4 py-3">
                                            Tujuan {yearLabel(to)}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {plan.rows.map((row) => (
                                        <tr key={row.source}>
                                            <td className="px-4 py-3 font-medium text-slate-900">
                                                {row.source}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {row.students}
                                            </td>
                                            <td className="px-4 py-3">
                                                {row.action === 'graduate' ? (
                                                    <StatusBadge
                                                        variant={
                                                            form.data
                                                                .graduate_final_grade
                                                                ? 'success'
                                                                : 'neutral'
                                                        }
                                                    >
                                                        {form.data
                                                            .graduate_final_grade
                                                            ? 'Lulus'
                                                            : 'Status siswa tidak diubah'}
                                                    </StatusBadge>
                                                ) : (
                                                    <span className="flex flex-wrap items-center gap-2">
                                                        <span className="font-medium text-slate-900">
                                                            {row.target}
                                                        </span>
                                                        <StatusBadge
                                                            variant={
                                                                row.target_exists
                                                                    ? 'neutral'
                                                                    : 'brand'
                                                            }
                                                        >
                                                            {row.target_exists
                                                                ? 'Sudah ada'
                                                                : 'Dibuat baru'}
                                                        </StatusBadge>
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {plan.new_empty.map((item) => (
                                        <tr
                                            key={`new-${item.name}`}
                                            className="bg-slate-50/60"
                                        >
                                            <td className="px-4 py-3 text-slate-500 italic">
                                                Siswa baru
                                            </td>
                                            <td className="px-4 py-3 text-right text-slate-400">
                                                –
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="flex flex-wrap items-center gap-2">
                                                    <span className="font-medium text-slate-900">
                                                        {item.name}
                                                    </span>
                                                    <StatusBadge
                                                        variant={
                                                            item.exists
                                                                ? 'neutral'
                                                                : 'brand'
                                                        }
                                                    >
                                                        {item.exists
                                                            ? 'Sudah ada'
                                                            : 'Dibuat kosong'}
                                                    </StatusBadge>
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <label className="flex items-start gap-3 text-sm text-slate-700">
                            <Checkbox
                                className="mt-0.5"
                                checked={form.data.graduate_final_grade}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'graduate_final_grade',
                                        checked === true,
                                    )
                                }
                            />
                            <span>
                                Tandai siswa tingkat akhir sebagai{' '}
                                <strong>Lulus</strong>
                                <span className="block text-xs text-slate-500">
                                    Jika tidak dicentang, rombel tingkat akhir
                                    tetap ditutup tetapi status siswa tidak
                                    diubah dan dapat diproses manual.
                                </span>
                            </span>
                        </label>
                        <ul className="mt-4 list-disc space-y-1 pl-5 text-xs text-slate-500">
                            <li>
                                Wali kelas dan penugasan mengajar tidak disalin;
                                atur ulang setelah naik kelas.
                            </li>
                            <li>
                                Siswa yang sudah punya rombel di TA tujuan
                                dilewati.
                            </li>
                            <li>
                                Riwayat rombel tahun asal tetap tersimpan dengan
                                status Selesai.
                            </li>
                        </ul>
                        <div className="mt-5 flex justify-end">
                            <Button
                                onClick={() => setConfirming(true)}
                                disabled={form.processing}
                            >
                                Proses naik kelas
                            </Button>
                        </div>
                    </section>
                </>
            )}

            <ConfirmDialog
                open={confirming}
                onOpenChange={setConfirming}
                title={`Proses naik kelas ke TA ${yearLabel(to)}?`}
                description={`${plan?.promote ?? 0} siswa akan dipindahkan ke rombel tahun berikutnya${
                    form.data.graduate_final_grade
                        ? ` dan ${plan?.graduate ?? 0} siswa ditandai lulus`
                        : ''
                }. Proses ini tidak dapat dibatalkan otomatis.`}
                confirmLabel="Ya, proses"
                processing={form.processing}
                onConfirm={submit}
            />
        </AppLayout>
    );
}
