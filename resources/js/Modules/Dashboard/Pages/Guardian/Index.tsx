import { Head } from '@inertiajs/react';
import { CalendarCheck2, Megaphone, NotebookPen, Wallet } from 'lucide-react';
import ParentLayout from '@/Layouts/ParentLayout';
import { DashboardPlaceholder } from '@/Modules/Dashboard/Components/DashboardPlaceholder';

export default function GuardianDashboard() {
    return (
        <ParentLayout>
            <Head title="Beranda" />
            <DashboardPlaceholder
                title="Beranda"
                stats={[
                    {
                        label: 'Kehadiran hari ini',
                        value: '—',
                        icon: CalendarCheck2,
                    },
                    { label: 'Nilai terbaru', value: '—', icon: NotebookPen },
                    { label: 'Tagihan jatuh tempo', value: '—', icon: Wallet },
                    { label: 'Pengumuman', value: '—', icon: Megaphone },
                ]}
            />
        </ParentLayout>
    );
}
