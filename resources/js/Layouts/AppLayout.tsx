import { usePage } from '@inertiajs/react';
import { AppSidebar } from '@/Components/app/AppSidebar';
import { AppTopbar } from '@/Components/app/AppTopbar';
import { FlashToaster } from '@/Components/shared/FlashToaster';
import { SidebarInset, SidebarProvider } from '@/Components/ui/sidebar';
import type { AppLayoutProps } from '@/types';

/**
 * Layout staf (kepala sekolah, guru, operator): sidebar navy + topbar putih.
 */
export default function AppLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const { sidebarOpen } = usePage().props;

    return (
        <SidebarProvider defaultOpen={sidebarOpen}>
            <AppSidebar />
            <SidebarInset className="min-w-0 bg-slate-50">
                <AppTopbar breadcrumbs={breadcrumbs} />
                <main className="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <div className="mx-auto w-full max-w-7xl space-y-6">
                        {children}
                    </div>
                </main>
            </SidebarInset>
            <FlashToaster />
        </SidebarProvider>
    );
}
