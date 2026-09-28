import { CalendarDays, Clock, MapPin, Users } from 'lucide-react';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatDate } from '@/lib/format';
import type { ActivityRow } from '@/Modules/Activity/types';
import type { StatusVariant } from '@/types';

export const TYPE_VARIANT: Record<ActivityRow['type']['value'], StatusVariant> =
    {
        SEKOLAH: 'brand',
        KELAS: 'info',
        HARIAN: 'neutral',
    };

/** Label peserta ringkas: "Seluruh sekolah" / "VII-A, VII-B · 2 siswa". */
export function participantsLabel(activity: ActivityRow): string {
    if (activity.type.value === 'SEKOLAH') {
        return 'Seluruh sekolah';
    }

    const parts = [
        activity.classrooms.map((c) => c.name).join(', '),
        activity.students.map((s) => s.name).join(', '),
    ].filter(Boolean);

    return parts.join(' · ') || '—';
}

export function timeLabel(activity: ActivityRow): string | null {
    if (!activity.start_time) {
        return null;
    }

    return activity.end_time
        ? `${activity.start_time}–${activity.end_time}`
        : activity.start_time;
}

/**
 * Baris metadata kegiatan: jenis, tanggal, jam, lokasi, peserta.
 */
export function ActivityMeta({
    activity,
    showDate = true,
}: {
    activity: ActivityRow;
    showDate?: boolean;
}) {
    const time = timeLabel(activity);

    return (
        <div className="space-y-1 text-sm text-slate-600">
            <div className="flex flex-wrap items-center gap-1.5">
                <StatusBadge variant={TYPE_VARIANT[activity.type.value]}>
                    {activity.type.label}
                </StatusBadge>
                {activity.status.value === 'DRAF' && (
                    <StatusBadge variant="neutral">Draf</StatusBadge>
                )}
            </div>
            {showDate && (
                <p className="flex items-center gap-1.5">
                    <CalendarDays className="size-4 shrink-0 text-slate-400" />
                    {formatDate(activity.date, 'EEEE, d MMMM yyyy')}
                </p>
            )}
            {time && (
                <p className="flex items-center gap-1.5 tabular-nums">
                    <Clock className="size-4 shrink-0 text-slate-400" />
                    {time}
                </p>
            )}
            {activity.location && (
                <p className="flex items-center gap-1.5">
                    <MapPin className="size-4 shrink-0 text-slate-400" />
                    {activity.location}
                </p>
            )}
            <p className="flex items-center gap-1.5">
                <Users className="size-4 shrink-0 text-slate-400" />
                <span className="line-clamp-1">
                    {participantsLabel(activity)}
                </span>
            </p>
        </div>
    );
}
