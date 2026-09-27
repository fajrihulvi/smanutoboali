import { GraduationCap } from 'lucide-react';
import { cn } from '@/lib/utils';

type Props = {
    /** 'light' untuk latar navy (sidebar/panel login), 'dark' untuk latar putih. */
    tone?: 'light' | 'dark';
    showText?: boolean;
    className?: string;
};

export function AppLogo({ tone = 'light', showText = true, className }: Props) {
    return (
        <span className={cn('flex items-center gap-2.5', className)}>
            <span className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-accent text-white shadow-sm">
                <GraduationCap className="size-5" />
            </span>
            {showText && (
                <span className="grid leading-tight">
                    <span
                        className={cn(
                            'text-base font-semibold tracking-tight',
                            tone === 'light' ? 'text-white' : 'text-brand-950',
                        )}
                    >
                        Skoolia
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
