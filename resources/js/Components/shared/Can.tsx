import type { ReactNode } from 'react';
import { usePermission } from '@/hooks/use-permission';

type Props = {
    /** Satu permission, atau beberapa (default: cukup salah satu). */
    permission: string | string[];
    /** true = semua permission wajib dimiliki. */
    all?: boolean;
    fallback?: ReactNode;
    children: ReactNode;
};

/**
 * Tampilkan children hanya bila role aktif memiliki permission.
 * Menyembunyikan elemen BUKAN otorisasi — backend tetap memeriksa.
 *
 *   <Can permission="user.manage"><Button>Tambah user</Button></Can>
 */
export function Can({
    permission,
    all = false,
    fallback = null,
    children,
}: Props) {
    const { can, canAny, canAll } = usePermission();

    const permissions = Array.isArray(permission) ? permission : [permission];
    const allowed =
        permissions.length === 1
            ? can(permissions[0])
            : all
              ? canAll(permissions)
              : canAny(permissions);

    return <>{allowed ? children : fallback}</>;
}
