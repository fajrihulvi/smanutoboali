import { Search, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

type Props = {
    search?: string;
    onSearchChange?: (value: string) => void;
    searchPlaceholder?: string;
    /** Filter tambahan (Select, dsb.). */
    children?: ReactNode;
    isActive?: boolean;
    onReset?: () => void;
};

/**
 * Baris pencarian + filter di atas tabel. Pakai bersama hook useFilters().
 */
export function FilterBar({
    search,
    onSearchChange,
    searchPlaceholder = 'Cari…',
    children,
    isActive = false,
    onReset,
}: Props) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
            {onSearchChange && (
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        type="search"
                        value={search ?? ''}
                        onChange={(event) => onSearchChange(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="pl-9"
                        aria-label="Cari"
                    />
                </div>
            )}
            {children && (
                <div className="flex flex-wrap items-center gap-2">
                    {children}
                </div>
            )}
            {isActive && onReset && (
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={onReset}
                    className="text-slate-500 sm:ml-auto"
                >
                    <X className="size-4" />
                    Reset filter
                </Button>
            )}
        </div>
    );
}
