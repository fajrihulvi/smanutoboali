export type ReportStatusValue =
    | 'DRAFT'
    | 'SUBMITTED'
    | 'HOMEROOM_REVIEWED'
    | 'VALIDATED'
    | 'APPROVED'
    | 'PUBLISHED'
    | 'REVISION';

export type ReportStatus = { value: ReportStatusValue; label: string };

export type ReportCardSummary = {
    public_id: string;
    student: { public_id: string; nis: string; name: string };
    status: ReportStatus;
    has_note: boolean;
    published_at: string | null;
};

export type SubjectConfirmation = {
    assignment: string;
    subject: string;
    teacher: string;
    confirmed_at: string | null;
};

export type StatusCounts = Record<ReportStatusValue, number>;
