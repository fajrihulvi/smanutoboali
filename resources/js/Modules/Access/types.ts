import type { Role } from '@/types';

export type UserRow = {
    public_id: string;
    name: string;
    email: string;
    roles: Role[];
    is_active: boolean;
    is_self: boolean;
    last_login_at: string | null;
};

export type RoleSummary = {
    code: string;
    label: string;
    is_core: boolean;
    users_count: number;
    permissions_count: number;
};

export type RoleDetail = {
    code: string;
    label: string;
    is_core: boolean;
    permissions: string[];
};

export type PermissionGroup = {
    group: string;
    permissions: { name: string; label: string }[];
};
