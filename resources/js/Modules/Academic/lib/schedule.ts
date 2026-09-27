import type { ScheduleEntry } from '@/Modules/Academic/types';

function toMinutes(time: string): number {
    const [hours, minutes] = time.split(':').map(Number);

    return hours * 60 + minutes;
}

/** Total durasi jadwal dalam menit. */
export function totalMinutes(entries: ScheduleEntry[]): number {
    return entries.reduce(
        (sum, entry) =>
            sum + toMinutes(entry.end_time) - toMinutes(entry.start_time),
        0,
    );
}

/** "3 jam 20 menit", "40 menit". */
export function formatDuration(minutes: number): string {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    if (hours === 0) {
        return `${rest} menit`;
    }

    return rest === 0 ? `${hours} jam` : `${hours} jam ${rest} menit`;
}
