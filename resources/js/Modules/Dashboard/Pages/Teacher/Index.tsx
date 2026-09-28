import { Head } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardCheck,
    HeartPulse,
    MessagesSquare,
} from 'lucide-react';
import { useChatUnread } from '@/hooks/use-chat-unread';
import AppLayout from '@/Layouts/AppLayout';
import { TeacherCheckInCard } from '@/Modules/Attendance/Components/TeacherCheckInCard';
import type { SelfAttendance } from '@/Modules/Attendance/Components/TeacherCheckInCard';
import { DashboardPlaceholder } from '@/Modules/Dashboard/Components/DashboardPlaceholder';

export default function TeacherDashboard({
    selfAttendance,
}: {
    selfAttendance: SelfAttendance;
}) {
    const chatUnread = useChatUnread();

    return (
        <AppLayout
            breadcrumbs={[{ title: 'Dashboard', href: '/guru/dashboard' }]}
        >
            <Head title="Dashboard Guru" />
            <DashboardPlaceholder
                title="Dashboard Guru"
                stats={[
                    {
                        label: 'Jadwal hari ini',
                        value: '—',
                        icon: CalendarDays,
                    },
                    {
                        label: 'Presensi belum diisi',
                        value: '—',
                        icon: ClipboardCheck,
                    },
                    {
                        label: 'Kondisi anak baru',
                        value: '—',
                        icon: HeartPulse,
                    },
                    {
                        label: 'Chat belum dibaca',
                        value: String(chatUnread),
                        icon: MessagesSquare,
                    },
                ]}
            >
                <TeacherCheckInCard data={selfAttendance} />
            </DashboardPlaceholder>
        </AppLayout>
    );
}
