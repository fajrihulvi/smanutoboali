import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

/**
 * Item navigasi generik (dipakai halaman pengaturan akun bawaan starter kit).
 */
export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

/**
 * Item menu aplikasi per role (resources/js/config/navigation.ts).
 */
export type MenuItem = {
    label: string;
    /** Path URL tujuan, contoh '/guru/presensi'. */
    href: string;
    icon: LucideIcon;
    /** Permission spatie yang dibutuhkan; tanpa nilai = semua user role ini. */
    permission?: string;
    /** Sumber angka badge realtime, mis. 'chat' = chat belum dibaca. */
    badge?: 'chat';
    children?: MenuItem[];
};
