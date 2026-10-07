export type Option = { value: string; label: string };

export type LinkedAccount = { email: string; is_active: boolean } | null;

export type TeacherRow = {
    public_id: string;
    name: string;
    nip: string | null;
    nuptk: string | null;
    gender: Option;
    phone: string | null;
    employment_status: Option;
    is_active: boolean;
    photo_url?: string | null;
    account?: LinkedAccount;
};

export type GuardianRow = {
    public_id: string;
    name: string;
    phone: string | null;
    email: string | null;
    occupation: string | null;
    address: string | null;
    nik_masked: string | null;
    account?: LinkedAccount;
};

export type StudentGuardian = {
    public_id: string;
    name: string;
    relationship: Option;
    is_primary: boolean;
    phone: string | null;
    email: string | null;
    has_account: boolean;
};

export type StudentRow = {
    public_id: string;
    nis: string;
    nisn: string | null;
    name: string;
    gender: Option;
    birth_place: string | null;
    birth_date: string | null;
    religion: Option | null;
    address: string | null;
    status: Option;
    entry_date: string | null;
    photo_url?: string | null;
    guardians?: StudentGuardian[];
};

export type ImportRow = {
    public_id: string;
    original_name: string;
    status: Option;
    finished: boolean;
    total_rows: number;
    success_rows: number;
    failed_rows: number;
    errors: { row: number; messages: string[] }[];
    failure_message: string | null;
    created_by?: string | null;
    created_at: string;
};
