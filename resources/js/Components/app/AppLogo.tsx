import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';

type Props = {
    /** 'light' untuk latar hijau tua (sidebar/panel login), 'dark' untuk latar putih. */
    tone?: 'light' | 'dark';
    showText?: boolean;
    className?: string;
};

/**
 * Logo + nama sekolah. Logo dari Pengaturan Sekolah, bawaan `public/images/logo.png`.
 */
export function AppLogo({ tone = 'light', showText = true, className }: Props) {
    const { school } = usePage().props;

    return (
        <span className={cn('flex items-center gap-2.5', className)}>
            <img
                src={school.logo_url}
                alt={showText ? '' : school.name}
                className="size-9 shrink-0 object-contain drop-shadow-sm"
            />
            {showText && (
                <span className="grid min-w-0 leading-tight">
                    <span
                        className={cn(
                            'truncate text-base font-semibold tracking-tight',
                            tone === 'light' ? 'text-white' : 'text-brand-950',
                        )}
                    >
                        {school.name}
                    </span>
                    <span
                        className={cn(
                            'text-[11px]',
                            tone === 'light'
                                ? 'text-white/60'
                                : 'text-slate-500',
                        )}
                    >
                        Sistem Informasi Sekolah
                    </span>
                </span>
            )}
        </span>
    );
}
