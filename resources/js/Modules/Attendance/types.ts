export type AttendanceStatusValue =
    'HADIR' | 'SAKIT' | 'IZIN' | 'ALPA' | 'TERLAMBAT';

export type StatusOption = { value: AttendanceStatusValue; label: string };

export type Counts = Record<AttendanceStatusValue, number>;

export type Summary = {
    counts: Counts;
    days: number;
    percentage: number | null;
};

export type ScheduleInfo = {
    public_id: string;
    subject: string;
    start_time: string;
    end_time: string;
    teacher: string;
};

export type StudentRef = { public_id: string; nis: string; name: string };

export type CalendarData = {
    days: Record<
        string,
        {
            status: AttendanceStatusValue;
            entries: {
                status: AttendanceStatusValue;
                subject: string | null;
                note: string | null;
            }[];
        }
    >;
    summary: Summary;
};
