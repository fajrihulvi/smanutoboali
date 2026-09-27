import { Head } from '@inertiajs/react';
import { ClipboardCheck, GraduationCap, Users, Wallet } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { DashboardPlaceholder } from '@/Modules/Dashboard/Components/DashboardPlaceholder';

export default function PrincipalDashboard() {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Dashboard', href: '/kepala-sekolah/dashboard' },
            ]}
        >
            <Head title="Dashboard Kepala Sekolah" />
            <DashboardPlaceholder
                title="Dashboard Kepala Sekolah"
                stats={[
                    { label: 'Total siswa', value: '—', icon: GraduationCap },
                    { label: 'Guru aktif', value: '—', icon: Users },
                    {
                        label: 'Kehadiran hari ini',
                        value: '—',
                        icon: ClipboardCheck,
                    },
                    { label: 'Tagihan terbayar', value: '—', icon: Wallet },
                ]}
            />
        </AppLayout>
    );
}
