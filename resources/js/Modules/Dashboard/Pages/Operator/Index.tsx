import { Head } from '@inertiajs/react';
import { Database, FileClock, UserRoundPlus, Wallet } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { DashboardPlaceholder } from '@/Modules/Dashboard/Components/DashboardPlaceholder';

export default function OperatorDashboard() {
    return (
        <AppLayout
            breadcrumbs={[{ title: 'Dashboard', href: '/operator/dashboard' }]}
        >
            <Head title="Dashboard Operator" />
            <DashboardPlaceholder
                title="Dashboard Operator"
                stats={[
                    { label: 'Data belum lengkap', value: '—', icon: Database },
                    {
                        label: 'Verifikasi PPDB',
                        value: '—',
                        icon: UserRoundPlus,
                    },
                    { label: 'Pembayaran menunggu', value: '—', icon: Wallet },
                    { label: 'Konten draft', value: '—', icon: FileClock },
                ]}
            />
        </AppLayout>
    );
}
