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
