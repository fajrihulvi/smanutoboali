export type ConditionTypeValue =
    | 'SAKIT'
    | 'KELUHAN_KESEHATAN'
    | 'EMOSIONAL'
    | 'IZIN_KHUSUS'
    | 'ALERGI_MAKANAN'
    | 'LAINNYA';

export type ConditionStatusValue =
    'SUBMITTED' | 'RECEIVED' | 'REVIEWED' | 'RESPONDED' | 'CLOSED';

export type ConditionRow = {
    public_id: string;
    type: { value: ConditionTypeValue; label: string };
    status: { value: ConditionStatusValue; label: string };
    description: string;
    valid_from: string;
    valid_until: string | null;
    student: { public_id: string; name: string };
    reporter: string | null;
    target_teacher: string | null;
    created_at: string;
    is_new: boolean;
};

export type ConditionDetail = ConditionRow & {
    photos: { public_id: string; name: string; url: string }[];
    responses: { author: string; body: string; created_at: string }[];
    timeline: { label: string; at: string }[];
    attendance_suggestion: string | null;
    can: { respond: boolean; review: boolean; withdraw: boolean };
};

export type TypeOption = {
    value: ConditionTypeValue;
    label: string;
    hint: string;
};
