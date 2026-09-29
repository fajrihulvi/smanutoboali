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
    History,
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
            children: [
                {
                    label: 'Jadwal',
                    href: '/kepala-sekolah/akademik',
                    icon: CalendarDays,
                },
                {
                    label: 'Nilai',
                    href: '/kepala-sekolah/nilai',
                    icon: NotebookPen,
                },
            ],
        },
        {
            label: 'Monitoring Presensi',
            href: '/kepala-sekolah/presensi',
            icon: ClipboardCheck,
            children: [
                {
                    label: 'Siswa',
                    href: '/kepala-sekolah/presensi',
                    icon: GraduationCap,
                },
                {
                    label: 'Guru',
                    href: '/kepala-sekolah/presensi-guru',
                    icon: Users,
                },
            ],
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
            children: [
                {
                    label: 'Dashboard',
                    href: '/kepala-sekolah/ppdb/dashboard',
                    icon: UserRoundPlus,
                },
                {
                    label: 'Seleksi',
                    href: '/kepala-sekolah/ppdb/seleksi',
                    icon: UserRoundPlus,
                },
                {
                    label: 'Pendaftar',
                    href: '/kepala-sekolah/ppdb/pendaftar',
                    icon: UserRoundPlus,
                },
            ],
        },
        {
            label: 'Laporan',
            href: '/kepala-sekolah/laporan',
            icon: BarChart3,
            permission: 'report.export',
        },
        {
            label: 'Audit Log',
            href: '/kepala-sekolah/audit-log',
            icon: History,
            permission: 'audit_log.view',
        },
        {
            label: 'Pengumuman',
            href: '/kepala-sekolah/pengumuman',
            icon: Megaphone,
            permission: 'announcement.manage',
        },
        {
            label: 'Kondisi Anak',
            href: '/kepala-sekolah/kondisi-anak',
            icon: HeartPulse,
        },
        {
            label: 'Chat',
            href: '/kepala-sekolah/chat',
            icon: MessagesSquare,
            permission: 'chat.use',
            badge: 'chat',
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
            label: 'Laporan',
            href: '/guru/laporan',
            icon: BarChart3,
            permission: 'report.export',
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
            label: 'Pengumuman',
            href: '/guru/pengumuman',
            icon: Megaphone,
        },
        {
            label: 'Chat',
            href: '/guru/chat',
            icon: MessagesSquare,
            permission: 'chat.use',
            badge: 'chat',
        },
        { label: 'Profil', href: '/guru/profil', icon: UserRound },
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
                    label: 'Mata Pelajaran',
                    href: '/operator/master-data/mapel',
                    icon: BookOpen,
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
            children: [
                {
                    label: 'Penugasan Mengajar',
                    href: '/operator/akademik/penugasan',
                    icon: Users,
                    permission: 'master_data.manage',
                },
                {
                    label: 'Jadwal Pelajaran',
                    href: '/operator/akademik/jadwal',
                    icon: CalendarDays,
                },
                {
                    label: 'Nilai',
                    href: '/operator/nilai',
                    icon: NotebookPen,
                    permission: 'grade.view',
                },
            ],
        },
        {
            label: 'Presensi',
            href: '/operator/presensi',
            icon: ClipboardCheck,
            permission: 'attendance.manage',
            children: [
                {
                    label: 'Presensi Siswa',
                    href: '/operator/presensi',
                    icon: GraduationCap,
                },
                {
                    label: 'Presensi Guru',
                    href: '/operator/presensi-guru',
                    icon: Users,
                },
            ],
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
            label: 'Chat',
            href: '/operator/chat',
            icon: MessagesSquare,
            permission: 'chat.use',
            badge: 'chat',
        },
        {
            label: 'Pengumuman',
            href: '/operator/pengumuman',
            icon: Megaphone,
            permission: 'announcement.manage',
        },
        {
            label: 'Keuangan',
            href: '/operator/keuangan',
            icon: Wallet,
            permission: 'finance.manage',
            children: [
                {
                    label: 'Tagihan',
                    href: '/operator/keuangan/tagihan',
                    icon: Wallet,
                },
                {
                    label: 'Buat Tagihan',
                    href: '/operator/keuangan/buat',
                    icon: Wallet,
                },
                {
                    label: 'Verifikasi',
                    href: '/operator/keuangan/verifikasi',
                    icon: Wallet,
                },
                {
                    label: 'Jenis Biaya',
                    href: '/operator/keuangan/jenis-biaya',
                    icon: Wallet,
                },
                {
                    label: 'Potongan',
                    href: '/operator/keuangan/potongan',
                    icon: Wallet,
                },
                {
                    label: 'Rekap',
                    href: '/operator/keuangan/rekap',
                    icon: BarChart3,
                },
            ],
        },
        {
            label: 'PPDB',
            href: '/operator/ppdb',
            icon: UserRoundPlus,
            permission: 'ppdb.manage',
            children: [
                {
                    label: 'Dashboard',
                    href: '/operator/ppdb/dashboard',
                    icon: UserRoundPlus,
                },
                {
                    label: 'Pendaftar',
                    href: '/operator/ppdb/pendaftar',
                    icon: UserRoundPlus,
                },
                {
                    label: 'Seleksi',
                    href: '/operator/ppdb/seleksi',
                    icon: UserRoundPlus,
                },
                {
                    label: 'Periode & Jalur',
                    href: '/operator/ppdb/periode',
                    icon: UserRoundPlus,
                },
            ],
        },
        {
            label: 'CMS Website',
            href: '/operator/cms',
            icon: Globe,
            permission: 'cms.manage',
            children: [
                {
                    label: 'Halaman',
                    href: '/operator/cms/halaman',
                    icon: Globe,
                },
                {
                    label: 'Berita',
                    href: '/operator/cms/berita',
                    icon: Globe,
                },
                {
                    label: 'Agenda',
                    href: '/operator/cms/agenda',
                    icon: Globe,
                },
                {
                    label: 'Galeri',
                    href: '/operator/cms/galeri',
                    icon: Globe,
                },
                {
                    label: 'Banner',
                    href: '/operator/cms/banner',
                    icon: Globe,
                },
                {
                    label: 'Kategori',
                    href: '/operator/cms/kategori',
                    icon: Globe,
                },
                {
                    label: 'Guru di Website',
                    href: '/operator/cms/guru',
                    icon: Globe,
                },
                {
                    label: 'Pengaturan',
                    href: '/operator/cms/pengaturan',
                    icon: Globe,
                },
            ],
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
            href: '/operator/laporan',
            icon: BarChart3,
            permission: 'report.export',
        },
        {
            label: 'Audit Log',
            href: '/operator/audit-log',
            icon: History,
            permission: 'audit_log.view',
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
        { label: 'Timeline Anak', href: '/orang-tua/timeline', icon: History },
        { label: 'Akademik', href: '/orang-tua/akademik', icon: Library },
        { label: 'Nilai', href: '/orang-tua/nilai', icon: NotebookPen },
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
            badge: 'chat',
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
