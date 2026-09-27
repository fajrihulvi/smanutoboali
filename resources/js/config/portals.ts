import type { LucideIcon } from 'lucide-react';
import {
    BookOpenCheck,
    Building2,
    HeartHandshake,
    School,
    UserRoundPlus,
} from 'lucide-react';
import type { RoleCode } from '@/types';

export type Portal = {
    role: RoleCode;
    title: string;
    tagline: string;
    icon: LucideIcon;
    /** Path halaman login portal. */
    loginPath: string;
};

/**
 * Metadata portal per role (halaman login & panel AuthLayout).
 * Prefix URL mengikuti tabel "Konvensi role" di CLAUDE.md.
 */
export const portals: Record<RoleCode, Portal> = {
    principal: {
        role: 'principal',
        title: 'Portal Kepala Sekolah',
        tagline: 'Pantau akademik, presensi, keuangan, dan persetujuan raport.',
        icon: Building2,
        loginPath: '/login/kepala-sekolah',
    },
    teacher: {
        role: 'teacher',
        title: 'Portal Guru',
        tagline:
            'Kelola kelas, presensi, nilai, dan komunikasi dengan orang tua.',
        icon: BookOpenCheck,
        loginPath: '/login/guru',
    },
    operator: {
        role: 'operator',
        title: 'Portal Operator',
        tagline: 'Kelola master data, pengguna, keuangan, PPDB, dan website.',
        icon: School,
        loginPath: '/login/operator',
    },
    guardian: {
        role: 'guardian',
        title: 'Portal Orang Tua',
        tagline: 'Ikuti perkembangan, kehadiran, dan tagihan anak Anda.',
        icon: HeartHandshake,
        loginPath: '/login/orang-tua',
    },
    applicant: {
        role: 'applicant',
        title: 'Portal PPDB',
        tagline: 'Daftarkan calon siswa dan pantau status pendaftaran.',
        icon: UserRoundPlus,
        loginPath: '/ppdb/masuk',
    },
};

/** Portal yang tampil di halaman pilihan /login. */
export const staffAndParentPortals: Portal[] = [
    portals.principal,
    portals.teacher,
    portals.operator,
    portals.guardian,
];
