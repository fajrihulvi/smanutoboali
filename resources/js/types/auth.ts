export type RoleCode =
    'principal' | 'teacher' | 'operator' | 'guardian' | 'applicant';

export type Role = {
    code: RoleCode;
    label: string;
    prefix: string;
};

/**
 * User yang sedang login (dibentuk oleh AuthUserResource, tanpa id numerik).
 */
export type User = {
    public_id: string;
    name: string;
    email: string;
    avatar: string | null;
    email_verified_at: string | null;
};

export type Auth = {
    user: User | null;
    roles: Role[];
    activeRole: RoleCode | null;
    permissions: string[];
};

export type Flash = {
    success: string | null;
    error: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
