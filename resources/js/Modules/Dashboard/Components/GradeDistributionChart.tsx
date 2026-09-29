import {
    Bar,
    BarChart,
    LabelList,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatNumber, formatPercent } from '@/lib/format';
import { ChartTable } from '@/Modules/Dashboard/Components/ChartTable';
import { PanelEmpty } from '@/Modules/Dashboard/Components/DashboardPanel';
import type { GradeSummary } from '@/Modules/Dashboard/types';

const PREDICATES = [
    { key: 'A', label: 'A · Sangat baik' },
    { key: 'B', label: 'B · Baik' },
    { key: 'C', label: 'C · Cukup' },
    { key: 'D', label: 'D · Perlu bimbingan' },
] as const;

const COLOR = {
    series: 'var(--color-brand-500)',
    active: 'var(--color-brand-600)',
    axis: 'var(--color-slate-600)',
    value: 'var(--color-slate-700)',
};

type Row = { label: string; count: number; share: number };

/**
 * Distribusi predikat nilai akhir (mapel × siswa) semester aktif — batang horizontal satu seri.
 */
export function GradeDistributionChart({
    summary,
}: {
    summary: GradeSummary | null;
}) {
    if (!summary || summary.graded === 0) {
        return (
            <PanelEmpty>
                Belum ada nilai akhir yang lengkap di semester ini.
            </PanelEmpty>
        );
    }

    const rows: Row[] = PREDICATES.map((p) => ({
        label: p.label,
        count: summary.distribution[p.key],
        share: (summary.distribution[p.key] / summary.graded) * 100,
    }));

    return (
        <div>
            <p className="mb-3 text-xs text-slate-500">
                Rata-rata{' '}
                <span className="font-semibold text-slate-900">
                    {summary.average?.toLocaleString('id-ID')}
                </span>{' '}
                · {formatNumber(summary.graded)} dari{' '}
                {formatNumber(summary.students)} nilai akhir lengkap ·{' '}
                {formatNumber(summary.below_kkm)} di bawah KKM
            </p>
            <div
                className="h-48"
                role="img"
                aria-label={`Distribusi predikat: ${rows.map((r) => `${r.label} ${r.count}`).join(', ')}`}
            >
                <ResponsiveContainer width="100%" height="100%">
                    <BarChart
                        data={rows}
                        layout="vertical"
                        margin={{ top: 0, right: 48, bottom: 0, left: 0 }}
                        barCategoryGap={10}
                    >
                        <XAxis type="number" hide />
                        <YAxis
                            type="category"
                            dataKey="label"
                            width={128}
                            tick={{ fill: COLOR.axis, fontSize: 12 }}
                            tickLine={false}
                            axisLine={false}
                        />
                        <Tooltip
                            cursor={false}
                            content={(props) => {
                                const row = props.active
                                    ? (props.payload?.[0]?.payload as
                                          Row | undefined)
                                    : undefined;

                                return row ? (
                                    <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-md">
                                        <p className="text-base font-semibold text-slate-900 tabular-nums">
                                            {formatNumber(row.count)}
                                        </p>
                                        <p className="text-slate-500">
                                            {row.label} ·{' '}
                                            {formatPercent(
                                                Math.round(row.share * 10) / 10,
                                            )}
                                        </p>
                                    </div>
                                ) : null;
                            }}
                        />
                        <Bar
                            dataKey="count"
                            fill={COLOR.series}
                            barSize={20}
                            radius={[0, 4, 4, 0]}
                            activeBar={{ fill: COLOR.active }}
                            isAnimationActive={false}
                        >
                            <LabelList
                                dataKey="count"
                                position="right"
                                fill={COLOR.value}
                                fontSize={12}
                                formatter={(v) => formatNumber(Number(v))}
                            />
                        </Bar>
                    </BarChart>
                </ResponsiveContainer>
            </div>
            <ChartTable
                caption="Distribusi predikat nilai akhir"
                headers={['Predikat', 'Jumlah', 'Persentase']}
                rows={rows.map((r) => [
                    r.label,
                    formatNumber(r.count),
                    formatPercent(Math.round(r.share * 10) / 10),
                ])}
            />
        </div>
    );
}
