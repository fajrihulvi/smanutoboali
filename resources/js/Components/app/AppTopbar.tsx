import { Search } from 'lucide-react';
import { NotificationBell } from '@/Components/app/NotificationBell';
import { RoleSwitcher } from '@/Components/app/RoleSwitcher';
import { UserMenu } from '@/Components/app/UserMenu';
import { Breadcrumbs } from '@/Components/breadcrumbs';
import { SidebarTrigger } from '@/Components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

export function AppTopbar({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItem[];
}) {
    return (
        <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur sm:px-6">
            <SidebarTrigger className="-ml-1 text-slate-500 hover:bg-brand-50 hover:text-brand-700" />

            <div className="hidden min-w-0 sm:block">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>

            <div className="ml-auto flex items-center gap-2">
                {/* Pencarian global: kerangka UI, fungsi menyusul. */}
                <label className="relative hidden lg:block">
                    <span className="sr-only">Pencarian global</span>
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <input
                        type="search"
                        placeholder="Cari siswa, guru, menu…"
                        disabled
                        title="Pencarian global segera hadir"
                        className="h-9 w-64 rounded-full border border-slate-200 bg-slate-50 pr-3 pl-9 text-sm placeholder:text-slate-400 disabled:cursor-not-allowed"
                    />
                </label>
                <RoleSwitcher />
                <NotificationBell />
                <UserMenu />
            </div>
        </header>
    );
}
