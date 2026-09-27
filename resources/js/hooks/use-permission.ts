import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';
import type { RoleCode } from '@/types';

/**
 * Cek permission role aktif di frontend (hanya untuk UX — otorisasi tetap di backend).
 *
 *   const { can } = usePermission();
 *   if (can('attendance.manage')) { ... }
 */
export function usePermission() {
    const { auth } = usePage().props;
    const granted = useMemo(
        () => new Set(auth.permissions),
        [auth.permissions],
    );

    const can = useCallback(
        (permission: string) => granted.has(permission),
        [granted],
    );

    const canAny = useCallback(
        (permissions: string[]) =>
            permissions.some((permission) => granted.has(permission)),
        [granted],
    );

    const canAll = useCallback(
        (permissions: string[]) =>
            permissions.every((permission) => granted.has(permission)),
        [granted],
    );

    const isRole = useCallback(
        (role: RoleCode) => auth.activeRole === role,
        [auth.activeRole],
    );

    return { can, canAny, canAll, isRole, activeRole: auth.activeRole };
}
