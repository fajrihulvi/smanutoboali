export type AttendanceCounts = Record<
    'HADIR' | 'SAKIT' | 'IZIN' | 'ALPA' | 'TERLAMBAT',
    number
>;

export type AttendanceToday = {
    recorded: number;
    unrecorded: number;
    percentage: number | null;
    counts: AttendanceCounts;
};

export type TrendPoint = {
    date: string;
    percentage: number;
    present: number;
    absent: number;
    sick: number;
    permit: number;
    recorded: number;
};

export type GradeSummary = {
    semester: string | null;
    students: number;
    graded: number;
    average: number | null;
    below_kkm: number;
    distribution: Record<'A' | 'B' | 'C' | 'D', number>;
};

export type AuditItem = {
    key: string;
    module: string;
    description: string;
    causer: string | null;
    created_at: string | null;
};

export type ChatItem = {
    public_id: string;
    url: string;
    name: string;
    student: string | null;
    preview: string | null;
    is_mine: boolean;
    unread: number;
    at: string | null;
};

export type TaskItem = {
    label: string;
    hint?: string;
    url: string;
};
