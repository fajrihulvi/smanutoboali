import { Link, router, usePage } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import type { ReactNode } from 'react';
import { AppLogo } from '@/Components/app/AppLogo';
import { NotificationBell } from '@/Components/app/NotificationBell';
import { FlashToaster } from '@/Components/shared/FlashToaster';
import { Button } from '@/Components/ui/button';

/**
 * Portal PPDB (calon orang tua): header ringkas, konten satu kolom, mobile-first.
 */
export default function ApplicantLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;

    return (
        <div className="min-h-dvh bg-slate-50">
            <header className="sticky top-0 z-30 bg-gradient-to-r from-brand-950 via-brand-900 to-brand-700 text-white shadow-sm">
                <div className="mx-auto flex h-14 max-w-4xl items-center gap-2 px-4">
                    <Link
                        href="/ppdb/akun/dashboard"
                        className="flex items-center gap-2"
                    >
                        <AppLogo showText={false} />
                        <span className="text-sm font-semibold">
                            Portal PPDB
                        </span>
                    </Link>
                    <div className="ml-auto flex items-center gap-1">
                        <span className="hidden max-w-40 truncate text-sm text-white/80 sm:inline">
                            {auth.user?.name}
                        </span>
                        <NotificationBell className="text-white/80 hover:bg-white/10 hover:text-white" />
                        <Button
                            variant="ghost"
                            size="icon"
                            className="text-white/80 hover:bg-white/10 hover:text-white"
                            onClick={() => router.post('/logout')}
                            aria-label="Keluar"
                        >
                            <LogOut />
                        </Button>
                    </div>
                </div>
            </header>
            <main className="mx-auto w-full max-w-4xl space-y-5 px-4 py-5 md:py-8">
                {children}
            </main>
            <FlashToaster />
        </div>
    );
}
