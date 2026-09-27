import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/**
 * <select> native bergaya design system — dipakai untuk form sederhana dan
 * matriks berisi ratusan sel (lebih ringan dari Radix Select).
 */
export function NativeSelect({
    className,
    children,
    ...props
}: ComponentProps<'select'>) {
    return (
        <select
            className={cn(
                'h-9 w-full rounded-md border border-input bg-white px-2.5 text-sm text-slate-800 shadow-xs transition-colors duration-150 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-rose-400',
                className,
            )}
            {...props}
        >
            {children}
        </select>
    );
}
