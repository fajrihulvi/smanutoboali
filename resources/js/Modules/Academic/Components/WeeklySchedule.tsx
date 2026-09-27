import { Clock, MapPin } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import type { DayOption, ScheduleEntry } from '@/Modules/Academic/types';

type Props = {
    days: DayOption[];
    schedules: ScheduleEntry[];
    /** Teks kedua di kartu: nama guru (tampilan rombel) atau nama rombel (tampilan guru). */
    detail: 'teacher' | 'classroom';
    today?: number | null;
    /** Sabtu selalu tampil (editor); tampilan baca menyembunyikannya bila kosong. */
    showEmptySaturday?: boolean;
    onSelect?: (entry: ScheduleEntry) => void;
    /** Slot di bawah tiap kolom hari, mis. tombol tambah. */
    renderDayFooter?: (day: DayOption) => ReactNode;
};

/**
 * Grid jadwal mingguan: kolom per hari, kartu urut jam. Di layar kecil (< sm) tampil
 * tab hari dan hanya hari terpilih (default hari ini) agar halaman tidak memanjang.
 */
export function WeeklySchedule({
    days,
    schedules,
    detail,
    today,
    showEmptySaturday = false,
    onSelect,
    renderDayFooter,
}: Props) {
    const visibleDays = days.filter(
        (day) =>
            day.value !== 6 ||
            showEmptySaturday ||
            schedules.some((entry) => entry.day === 6),
    );
    const [mobileDay, setMobileDay] = useState<number>(
        visibleDays.find((day) => day.value === today)?.value ??
            visibleDays[0]?.value ??
            1,
    );

    return (
        <div className="space-y-3">
            <div
                role="tablist"
                aria-label="Pilih hari"
                className="-mx-4 flex gap-1.5 overflow-x-auto px-4 sm:hidden"
            >
                {visibleDays.map((day) => (
                    <button
                        key={day.value}
                        type="button"
                        role="tab"
                        aria-selected={mobileDay === day.value}
                        onClick={() => setMobileDay(day.value)}
                        className={cn(
                            'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors duration-150',
                            mobileDay === day.value
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600',
                        )}
                    >
                        {day.label.slice(0, 3)}
                        {today === day.value && ' •'}
                    </button>
                ))}
            </div>
            <div
                className={cn(
                    'grid gap-3 sm:grid-cols-2 lg:grid-cols-3',
                    visibleDays.length > 5
                        ? 'xl:grid-cols-6'
                        : 'xl:grid-cols-5',
                )}
            >
                {visibleDays.map((day) => {
                    const entries = schedules.filter(
                        (entry) => entry.day === day.value,
                    );
                    const isToday = today === day.value;

                    return (
                        <section
                            key={day.value}
                            aria-label={day.label}
                            className={cn(
                                'min-w-0 flex-col rounded-2xl border bg-white shadow-sm',
                                mobileDay === day.value
                                    ? 'flex'
                                    : 'hidden sm:flex',
                                isToday
                                    ? 'border-brand-300 ring-1 ring-brand-200'
                                    : 'border-slate-200',
                            )}
                        >
                            <header className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <h3 className="text-sm font-semibold text-slate-900">
                                    {day.label}
                                </h3>
                                {isToday ? (
                                    <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">
                                        Hari ini
                                    </span>
                                ) : (
                                    <span className="text-xs text-slate-400 tabular-nums">
                                        {entries.length} jam
                                    </span>
                                )}
                            </header>
                            <ol className="flex flex-1 flex-col gap-2 p-3">
                                {entries.length === 0 && (
                                    <li className="py-4 text-center text-xs text-slate-400">
                                        Tidak ada jadwal
                                    </li>
                                )}
                                {entries.map((entry) => {
                                    const body = (
                                        <>
                                            <p className="flex items-center gap-1 text-xs font-medium text-brand-700 tabular-nums">
                                                <Clock className="size-3" />
                                                {entry.start_time}–
                                                {entry.end_time}
                                            </p>
                                            <p className="mt-0.5 truncate text-sm font-semibold text-slate-900">
                                                {entry.subject.name}
                                            </p>
                                            <p className="truncate text-xs text-slate-500">
                                                {detail === 'teacher'
                                                    ? entry.teacher.name
                                                    : entry.classroom.name}
                                            </p>
                                            {entry.room && (
                                                <p className="mt-0.5 flex items-center gap-1 truncate text-xs text-slate-400">
                                                    <MapPin className="size-3" />
                                                    {entry.room}
                                                </p>
                                            )}
                                        </>
                                    );

                                    return (
                                        <li key={entry.public_id}>
                                            {onSelect ? (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        onSelect(entry)
                                                    }
                                                    className="w-full rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2 text-left transition-colors duration-150 hover:border-brand-300 hover:bg-brand-50/40 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none"
                                                    aria-label={`Ubah jadwal ${entry.subject.name} ${day.label} ${entry.start_time}`}
                                                >
                                                    {body}
                                                </button>
                                            ) : (
                                                <div className="rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2">
                                                    {body}
                                                </div>
                                            )}
                                        </li>
                                    );
                                })}
                            </ol>
                            {renderDayFooter && (
                                <div className="border-t border-slate-100 p-2">
                                    {renderDayFooter(day)}
                                </div>
                            )}
                        </section>
                    );
                })}
            </div>
        </div>
    );
}
