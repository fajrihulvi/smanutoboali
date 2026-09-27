import {
    Baby,
    BarChart3,
    BookOpen,
    CalendarDays,
    ClipboardCheck,
    FileText,
    FolderKanban,
    GraduationCap,
    HeartPulse,
    House,
    KeyRound,
    LayoutDashboard,
    Library,
    Megaphone,
    MessagesSquare,
    NotebookPen,
    ScrollText,
    Settings,
    Shapes,
    ShieldCheck,
    UserRound,
    UserRoundPlus,
    Users,
    Wallet,
    Globe,
    Database,
    PartyPopper,
} from 'lucide-react';
import type { MenuItem, RoleCode } from '@/types';

/**
 * Menu aplikasi per role — SKOOLIA_SYSTEM_PLANNING §5.
 * Permission mengikuti matriks VIBE_PLANNING §4.2 (format modul.aksi).
 * Menu disaring otomatis oleh filterNavigation() berdasarkan permission user.
 */
export const navigation: Record<RoleCode, MenuItem[]> = {
    principal: [
        {
            label: 'Dashboard',
            href: '/kepala-sekolah/dashboard',
            icon: LayoutDashboard,
        },
        {
            label: 'Monitoring Akademik',
            href: '/kepala-sekolah/akademik',
            icon: Library,
        },
        {
            label: 'Monitoring Presensi',
            href: '/kepala-sekolah/presensi',
            icon: ClipboardCheck,
        },
        {
            label: 'Monitoring Siswa',
            href: '/kepala-sekolah/siswa',
            icon: GraduationCap,
            permission: 'student.view',
        },
        { label: 'Monitoring Guru', href: '/kepala-sekolah/guru', icon: Users },
        {
            label: 'Raport',
            href: '/kepala-sekolah/raport',
            icon: FileText,
            permission: 'report_card.approve',
        },
        {
            label: 'Portofolio',
            href: '/kepala-sekolah/portofolio',
            icon: FolderKanban,
        },
        {
            label: 'Kegiatan',
            href: '/kepala-sekolah/kegiatan',
            icon: PartyPopper,
        },
        {
            label: 'Keuangan',
            href: '/kepala-sekolah/keuangan',
            icon: Wallet,
            permission: 'finance.view',
        },
        {
            label: 'PPDB',
            href: '/kepala-sekolah/ppdb',
            icon: UserRoundPlus,
            permission: 'ppdb.decide',
        },
        {
            label: 'Laporan',
            href: '/kepala-sekolah/laporan',
            icon: BarChart3,
            permission: 'report.export',
        },
        {
            label: 'Chat',
            href: '/kepala-sekolah/chat',
            icon: MessagesSquare,
            permission: 'chat.use',
        },
        { label: 'Profil', href: '/settings/profile', icon: UserRound },
    ],
    teacher: [
        { label: 'Dashboard', href: '/guru/dashboard', icon: LayoutDashboard },
        { label: 'Kelas Saya', href: '/guru/kelas', icon: Shapes },
        {
            label: 'Siswa',
            href: '/guru/siswa',
            icon: GraduationCap,
            permission: 'student.view',
        },
        {
            label: 'Akademik',
            href: '/guru/akademik',
            icon: Library,
            permission: 'academic.manage',
            children: [
                {
                    label: 'Jadwal',
                    href: '/guru/akademik/jadwal',
                    icon: CalendarDays,
                },
                {
                    label: 'Materi',
                    href: '/guru/akademik/materi',
                    icon: BookOpen,
                },
                {
                    label: 'Nilai',
                    href: '/guru/akademik/nilai',
                    icon: NotebookPen,
                    permission: 'grade.manage',
                },
            ],
        },
        {
            label: 'Presensi',
            href: '/guru/presensi',
            icon: ClipboardCheck,
            permission: 'attendance.manage',
        },
        {
            label: 'Raport',
            href: '/guru/raport',
            icon: FileText,
            permission: 'report_card.review',
        },
        {
            label: 'Portofolio',
            href: '/guru/portofolio',
            icon: FolderKanban,
            permission: 'portfolio.manage',
        },
        {
            label: 'Kegiatan',
            href: '/guru/kegiatan',
            icon: PartyPopper,
            permission: 'activity.manage',
        },
        {
            label: 'Kondisi Anak',
            href: '/guru/kondisi-anak',
            icon: HeartPulse,
            permission: 'child_condition.respond',
        },
        {
            label: 'Chat',
            href: '/guru/chat',
            icon: MessagesSquare,
            permission: 'chat.use',
        },
        { label: 'Profil', href: '/settings/profile', icon: UserRound },
    ],
    operator: [
        {
            label: 'Dashboard',
            href: '/operator/dashboard',
            icon: LayoutDashboard,
        },
        {
            label: 'Master Data',
            href: '/operator/master-data',
            icon: Database,
            permission: 'master_data.manage',
            children: [
                {
                    label: 'Siswa',
                    href: '/operator/master-data/siswa',
                    icon: GraduationCap,
                },
                {
                    label: 'Guru',
                    href: '/operator/master-data/guru',
                    icon: Users,
                },
                {
                    label: 'Orang Tua',
                    href: '/operator/master-data/orang-tua',
                    icon: Baby,
                },
                {
                    label: 'Kelas',
                    href: '/operator/master-data/kelas',
                    icon: Shapes,
                },
                {
                    label: 'Tahun Ajaran',
                    href: '/operator/master-data/tahun-ajaran',
                    icon: CalendarDays,
                },
            ],
        },
        {
            label: 'Akademik',
            href: '/operator/akademik',
            icon: Library,
            permission: 'academic.manage',
        },
        {
            label: 'Presensi',
            href: '/operator/presensi',
            icon: ClipboardCheck,
            permission: 'attendance.manage',
        },
        {
            label: 'Raport',
            href: '/operator/raport',
            icon: FileText,
            permission: 'report_card.validate',
        },
        {
            label: 'Portofolio',
            href: '/operator/portofolio',
            icon: FolderKanban,
            permission: 'portfolio.manage',
        },
        {
            label: 'Kegiatan',
            href: '/operator/kegiatan',
            icon: PartyPopper,
            permission: 'activity.manage',
        },
        {
            label: 'Keuangan',
            href: '/operator/keuangan',
            icon: Wallet,
            permission: 'finance.manage',
        },
        {
            label: 'PPDB',
            href: '/operator/ppdb',
            icon: UserRoundPlus,
            permission: 'ppdb.manage',
        },
        {
            label: 'CMS Website',
            href: '/operator/cms',
            icon: Globe,
            permission: 'cms.manage',
        },
        {
            label: 'User Management',
            href: '/operator/users',
            icon: KeyRound,
            permission: 'user.manage',
        },
        {
            label: 'Role & Permission',
            href: '/operator/roles',
            icon: ShieldCheck,
            permission: 'role.manage',
        },
        {
            label: 'Laporan',
            href: '/operator/reports',
            icon: BarChart3,
            permission: 'report.export',
        },
        {
            label: 'Pengaturan Sekolah',
            href: '/operator/settings',
            icon: Settings,
            permission: 'setting.manage',
        },
        { label: 'Profil', href: '/settings/profile', icon: UserRound },
    ],
    guardian: [
        { label: 'Beranda', href: '/orang-tua/dashboard', icon: House },
        {
            label: 'Anak Saya',
            href: '/orang-tua/anak',
            icon: Baby,
            permission: 'student.view',
        },
        { label: 'Akademik', href: '/orang-tua/akademik', icon: Library },
        {
            label: 'Presensi',
            href: '/orang-tua/presensi',
            icon: ClipboardCheck,
        },
        { label: 'Raport', href: '/orang-tua/raport', icon: FileText },
        {
            label: 'Portofolio',
            href: '/orang-tua/portofolio',
            icon: FolderKanban,
        },
        { label: 'Kegiatan', href: '/orang-tua/kegiatan', icon: PartyPopper },
        {
            label: 'Tagihan',
            href: '/orang-tua/keuangan',
            icon: Wallet,
            permission: 'finance.view',
        },
        {
            label: 'Chat',
            href: '/orang-tua/chat',
            icon: MessagesSquare,
            permission: 'chat.use',
        },
        {
            label: 'Kondisi Anak',
            href: '/orang-tua/kondisi-anak',
            icon: HeartPulse,
            permission: 'child_condition.submit',
        },
        { label: 'Pengumuman', href: '/orang-tua/pengumuman', icon: Megaphone },
        { label: 'Profil', href: '/orang-tua/profil', icon: UserRound },
    ],
    applicant: [
        {
            label: 'Status Pendaftaran',
            href: '/ppdb/akun/dashboard',
            icon: ScrollText,
        },
    ],
};

/** Bottom navigation Portal Orang Tua (VIBE_PLANNING §6.3). */
export const guardianBottomNav: string[] = [
    '/orang-tua/dashboard',
    '/orang-tua/anak',
    '/orang-tua/chat',
    '/orang-tua/keuangan',
    '/orang-tua/profil',
];

/**
 * Saring menu berdasarkan permission. Item induk yang semua anaknya
 * tersaring ikut disembunyikan. Ini hanya UX — otorisasi tetap di backend.
 */
export function filterNavigation(
    items: MenuItem[],
    permissions: string[],
): MenuItem[] {
    const granted = new Set(permissions);

    return items.flatMap((item) => {
        if (item.permission && !granted.has(item.permission)) {
            return [];
        }

        if (!item.children) {
            return [item];
        }

        const children = filterNavigation(item.children, permissions);

        return children.length > 0 ? [{ ...item, children }] : [];
    });
}

export function navigationFor(
    role: RoleCode | null,
    permissions: string[],
): MenuItem[] {
    return role ? filterNavigation(navigation[role], permissions) : [];
}
