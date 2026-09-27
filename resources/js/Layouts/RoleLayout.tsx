import { usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import type { AppLayoutProps } from '@/types';

/**
 * Layout untuk halaman lintas role (profil, notifikasi): ParentLayout bila
 * role aktif orang tua, selain itu AppLayout.
 */
export default function RoleLayout({ children, breadcrumbs }: AppLayoutProps) {
    const { auth } = usePage().props;

    if (auth.activeRole === 'guardian') {
        return <ParentLayout>{children}</ParentLayout>;
    }

    return <AppLayout breadcrumbs={breadcrumbs}>{children}</AppLayout>;
}
