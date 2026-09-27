import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Repeat2 } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import type { RoleCode } from '@/types';

/**
 * Pengalih role aktif — hanya tampil bila user punya lebih dari satu role.
 */
export function RoleSwitcher({ className }: { className?: string }) {
    const { auth } = usePage().props;

    if (auth.roles.length < 2) {
        return null;
    }

    const active = auth.roles.find((role) => role.code === auth.activeRole);

    const switchTo = (code: RoleCode) => {
        if (code !== auth.activeRole) {
            router.post(`/switch-role/${code}`);
        }
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(
                    'inline-flex h-9 items-center gap-1.5 rounded-full border border-brand-100 bg-brand-50 px-3 text-xs font-semibold text-brand-700 transition-colors duration-150 hover:bg-brand-100',
                    className,
                )}
            >
                <Repeat2 className="size-3.5" />
                <span className="max-w-28 truncate">
                    {active?.label ?? 'Pilih role'}
                </span>
                <ChevronsUpDown className="size-3.5 opacity-60" />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel className="text-xs text-slate-500">
                    Masuk sebagai
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                {auth.roles.map((role) => (
                    <DropdownMenuItem
                        key={role.code}
                        onSelect={() => switchTo(role.code)}
                        className="justify-between"
                    >
                        {role.label}
                        {role.code === auth.activeRole && (
                            <Check className="size-4 text-brand-600" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
