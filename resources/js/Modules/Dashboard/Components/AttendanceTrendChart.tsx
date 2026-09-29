import {
    Area,
    AreaChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { formatDate, formatPercent } from '@/lib/format';
import { ChartTable } from '@/Modules/Dashboard/Components/ChartTable';
import { PanelEmpty } from '@/Modules/Dashboard/Components/DashboardPanel';
import type { TrendPoint } from '@/Modules/Dashboard/types';

/** Warna dari token design system (CSS variable Tailwind), bukan hex. */
const COLOR = {
    series: 'var(--color-brand-500)',
    grid: 'var(--color-slate-200)',
    axis: 'var(--color-slate-500)',
    cursor: 'var(--color-slate-300)',
    surface: 'var(--color-white)',
};

function TrendTooltip({ point }: { point?: TrendPoint }) {
    if (!point) {
        return null;
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs shadow-md">
            <p className="text-base font-semibold text-slate-900 tabular-nums">
                {formatPercent(point.percentage)}
            </p>
            <p className="text-slate-500">
                {formatDate(point.date, 'EEEE, d MMM yyyy')}
            </p>
            <p className="mt-1 text-slate-600 tabular-nums">
                Hadir {point.present} · Sakit {point.sick} · Izin {point.permit}{' '}
                · Alpa {point.absent}
            </p>
        </div>
    );
}

/**
 * Tren persentase kehadiran siswa harian (satu seri; hari tanpa presensi tidak ditampilkan).
 */
export function AttendanceTrendChart({ data }: { data: TrendPoint[] }) {
    if (data.length === 0) {
        return (
            <PanelEmpty>Belum ada data presensi 30 hari terakhir.</PanelEmpty>
        );
    }

    const average =
        data.reduce((sum, point) => sum + point.percentage, 0) / data.length;
    const latest = data[data.length - 1];

    return (
        <div>
            <p className="mb-3 text-xs text-slate-500">
                Terakhir{' '}
                <span className="font-semibold text-slate-900">
                    {formatPercent(latest.percentage)}
                </span>{' '}
                ({formatDate(latest.date, 'd MMM')}) · rata-rata{' '}
                <span className="font-semibold text-slate-900">
                    {formatPercent(Math.round(average * 10) / 10)}
                </span>
            </p>
            <div
                className="h-60"
                role="img"
                aria-label={`Grafik tren kehadiran ${data.length} hari, rata-rata ${formatPercent(Math.round(average * 10) / 10)}`}
            >
                <ResponsiveContainer width="100%" height="100%">
                    <AreaChart
                        data={data}
                        margin={{ top: 8, right: 12, bottom: 0, left: -16 }}
                    >
                        <CartesianGrid vertical={false} stroke={COLOR.grid} />
                        <XAxis
                            dataKey="date"
                            tickFormatter={(d: string) =>
                                formatDate(d, 'd MMM')
                            }
                            tick={{ fill: COLOR.axis, fontSize: 11 }}
                            tickLine={false}
                            axisLine={{ stroke: COLOR.grid }}
                            minTickGap={24}
                        />
                        <YAxis
                            domain={[0, 100]}
                            ticks={[0, 25, 50, 75, 100]}
                            tickFormatter={(v: number) => `${v}%`}
                            tick={{ fill: COLOR.axis, fontSize: 11 }}
                            tickLine={false}
                            axisLine={false}
                        />
                        <Tooltip
                            cursor={{ stroke: COLOR.cursor, strokeWidth: 1 }}
                            content={(props) => (
                                <TrendTooltip
                                    point={
                                        props.active
                                            ? (props.payload?.[0]?.payload as
                                                  TrendPoint | undefined)
                                            : undefined
                                    }
                                />
                            )}
                        />
                        <Area
                            type="monotone"
                            dataKey="percentage"
                            stroke={COLOR.series}
                            strokeWidth={2}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            fill={COLOR.series}
                            fillOpacity={0.1}
                            dot={false}
                            activeDot={{
                                r: 5,
                                fill: COLOR.series,
                                stroke: COLOR.surface,
                                strokeWidth: 2,
                            }}
                            isAnimationActive={false}
                        />
                    </AreaChart>
                </ResponsiveContainer>
            </div>
            <ChartTable
                caption="Kehadiran siswa per hari"
                headers={[
                    'Tanggal',
                    'Kehadiran',
                    'Hadir',
                    'Sakit',
                    'Izin',
                    'Alpa',
                ]}
                rows={[...data]
                    .reverse()
                    .map((p) => [
                        formatDate(p.date, 'EEE, d MMM'),
                        formatPercent(p.percentage),
                        p.present,
                        p.sick,
                        p.permit,
                        p.absent,
                    ])}
            />
        </div>
    );
}
