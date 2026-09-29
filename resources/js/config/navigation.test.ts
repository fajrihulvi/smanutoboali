import { describe, expect, it } from 'vitest';
import { navigationFor } from '@/config/navigation';
import type { MenuItem, RoleCode } from '@/types';
import rolePermissions from '../../../tests/fixtures/role-permissions.json';

/** Label menu (anak ditulis "Induk › Anak") untuk perbandingan yang mudah dibaca. */
function labels(items: MenuItem[]): string[] {
    return items.flatMap((item) => [
        item.label,
        ...(item.children ?? []).map(
            (child) => `${item.label} › ${child.label}`,
        ),
    ]);
}

function menuFor(role: RoleCode): string[] {
    return labels(navigationFor(role, rolePermissions[role]));
}

/**
 * Menu yang tampil per role dengan permission default (matriks VIBE_PLANNING §4.2
 * dan struktur menu SKOOLIA_SYSTEM_PLANNING §5).
 */
describe('navigasi per role sesuai matriks permission', () => {
    it('kepala sekolah', () => {
        expect(menuFor('principal')).toEqual([
            'Dashboard',
            'Monitoring Akademik',
            'Monitoring Akademik › Jadwal',
            'Monitoring Akademik › Nilai',
            'Monitoring Presensi',
            'Monitoring Presensi › Siswa',
            'Monitoring Presensi › Guru',
            'Monitoring Siswa',
            'Monitoring Guru',
            'Raport',
            'Portofolio',
            'Kegiatan',
            'Keuangan',
            'PPDB',
            'PPDB › Dashboard',
            'PPDB › Seleksi',
            'PPDB › Pendaftar',
            'Laporan',
            'Audit Log',
            'Pengumuman',
            'Kondisi Anak',
            'Chat',
            'Profil',
        ]);
    });

    it('guru', () => {
        expect(menuFor('teacher')).toEqual([
            'Dashboard',
            'Kelas Saya',
            'Siswa',
            'Akademik',
            'Akademik › Jadwal',
            'Akademik › Materi',
            'Akademik › Nilai',
            'Presensi',
            'Raport',
            'Portofolio',
            'Kegiatan',
            'Kondisi Anak',
            'Pengumuman',
            'Chat',
            'Profil',
        ]);
    });

    it('operator', () => {
        expect(menuFor('operator')).toEqual([
            'Dashboard',
            'Master Data',
            'Master Data › Siswa',
            'Master Data › Guru',
            'Master Data › Orang Tua',
            'Master Data › Kelas',
            'Master Data › Mata Pelajaran',
            'Master Data › Tahun Ajaran',
            'Akademik',
            'Akademik › Penugasan Mengajar',
            'Akademik › Jadwal Pelajaran',
            'Akademik › Nilai',
            'Presensi',
            'Presensi › Presensi Siswa',
            'Presensi › Presensi Guru',
            'Raport',
            'Portofolio',
            'Kegiatan',
            'Chat',
            'Pengumuman',
            'Keuangan',
            'Keuangan › Tagihan',
            'Keuangan › Buat Tagihan',
            'Keuangan › Verifikasi',
            'Keuangan › Jenis Biaya',
            'Keuangan › Potongan',
            'Keuangan › Rekap',
            'PPDB',
            'PPDB › Dashboard',
            'PPDB › Pendaftar',
            'PPDB › Seleksi',
            'PPDB › Periode & Jalur',
            'CMS Website',
            'CMS Website › Halaman',
            'CMS Website › Berita',
            'CMS Website › Agenda',
            'CMS Website › Galeri',
            'CMS Website › Banner',
            'CMS Website › Kategori',
            'CMS Website › Guru di Website',
            'CMS Website › Pengaturan',
            'User Management',
            'Role & Permission',
            'Laporan',
            'Audit Log',
            'Pengaturan Sekolah',
            'Profil',
        ]);
    });

    it('orang tua', () => {
        expect(menuFor('guardian')).toEqual([
            'Beranda',
            'Anak Saya',
            'Timeline Anak',
            'Akademik',
            'Nilai',
            'Presensi',
            'Raport',
            'Portofolio',
            'Kegiatan',
            'Tagihan',
            'Chat',
            'Kondisi Anak',
            'Pengumuman',
            'Profil',
        ]);
    });

    it('menyembunyikan menu yang permission-nya dicabut', () => {
        const withoutChat = rolePermissions.teacher.filter(
            (p) => p !== 'chat.use',
        );

        expect(labels(navigationFor('teacher', withoutChat))).not.toContain(
            'Chat',
        );
    });

    it('menyembunyikan menu induk bila semua anaknya tersaring', () => {
        const items = navigationFor('teacher', ['academic.manage']);
        const akademik = items.find((item) => item.label === 'Akademik');

        expect(akademik?.children?.map((child) => child.label)).toEqual([
            'Jadwal',
            'Materi',
        ]);
        expect(
            navigationFor('teacher', []).map((item) => item.label),
        ).not.toContain('Akademik');
    });

    it('tanpa role aktif tidak ada menu', () => {
        expect(navigationFor(null, rolePermissions.operator)).toEqual([]);
    });
});
