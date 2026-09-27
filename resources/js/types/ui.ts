import type { ReactNode } from 'react';
import type { RoleCode } from '@/types/auth';
import type { BreadcrumbItem } from '@/types/navigation';

export type AppLayoutProps = {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
};

export type AuthLayoutProps = {
    children?: ReactNode;
    title: string;
    description?: string;
    /** Portal yang sedang dibuka; menentukan judul & ikon panel biru. */
    portal?: RoleCode | null;
};

export type StatusVariant =
    'success' | 'warning' | 'danger' | 'info' | 'neutral' | 'brand';
