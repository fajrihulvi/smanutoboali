import type { Option } from '@/Modules/Academic/types';

export type ApplicantStatusValue =
    | 'DRAFT'
    | 'SUBMITTED'
    | 'NEEDS_REVISION'
    | 'VERIFIED'
    | 'IN_SELECTION'
    | 'ACCEPTED'
    | 'REJECTED'
    | 'RE_REGISTERED';

export type ApplicantRow = {
    public_id: string;
    registration_number: string | null;
    status: { value: ApplicantStatusValue; label: string };
    step: number;
    name: string | null;
    period: {
        public_id: string;
        name: string;
        grade: number;
        announces_at: string | null;
    };
    track: { public_id: string; name: string };
    revision_note: string | null;
    submitted_at: string | null;
    created_at: string;
    reregistration_submitted_at: string | null;
    reregistered_at: string | null;
    can: { update: boolean; delete: boolean; reregister: boolean };
    /** Hanya untuk staf. */
    selection?: {
        score: number | null;
        note: string | null;
        proposal: { value: 'DITERIMA' | 'TIDAK'; label: string } | null;
        decided_at: string | null;
        result_notified_at: string | null;
    };
    verified_at?: string | null;
};

export type StudentData = {
    name: string | null;
    nik: string | null;
    nisn: string | null;
    gender: string | null;
    birth_place: string | null;
    birth_date: string | null;
    religion: string | null;
    address: string | null;
    previous_school: string | null;
};

export type GuardianData = {
    relationship: string;
    name: string;
    nik: string | null;
    occupation: string | null;
    phone: string | null;
    email: string | null;
    is_primary: boolean;
};

export type Requirement = {
    public_id: string;
    name: string;
    description: string | null;
    is_required: boolean;
    document: {
        public_id: string;
        status: { value: 'PENDING' | 'VALID' | 'INVALID'; label: string };
        note: string | null;
        file: { name: string; url: string; is_image: boolean; size: number };
    } | null;
};

export type ApplicantDetail = ApplicantRow & {
    student: StudentData;
    guardians: GuardianData[];
    requirements: Requirement[];
};

export type FormOptions = {
    genders: Option[];
    religions: Option[];
    relationships: Option[];
};

export type PeriodRow = {
    public_id: string;
    name: string;
    academic_year: { public_id: string; name: string };
    grade: number;
    opens_at: string;
    closes_at: string;
    announces_at: string | null;
    description: string | null;
    is_active: boolean;
    is_open: boolean;
    applicants_count: number;
    tracks: {
        public_id: string;
        name: string;
        quota: number;
        description: string | null;
        requirements: {
            public_id: string;
            name: string;
            description: string | null;
            is_required: boolean;
        }[];
    }[];
};
