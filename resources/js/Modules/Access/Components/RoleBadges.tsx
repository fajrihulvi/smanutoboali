import { StatusBadge } from '@/Components/shared/StatusBadge';
import type { Role } from '@/types';

export function RoleBadges({ roles }: { roles: Role[] }) {
    if (roles.length === 0) {
        return <span className="text-xs text-slate-400">Tanpa role</span>;
    }

    return (
        <div className="flex flex-wrap gap-1">
            {roles.map((role) => (
                <StatusBadge key={role.code} variant="brand">
                    {role.label}
                </StatusBadge>
            ))}
        </div>
    );
}
