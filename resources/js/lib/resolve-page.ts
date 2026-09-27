import type { ComponentType } from 'react';

type PageModule = { default: ComponentType };
type PageLoader = () => Promise<PageModule>;

const modulePages = import.meta.glob<PageModule>('../Modules/*/Pages/**/*.tsx');
const legacyPages = import.meta.glob<PageModule>('../pages/**/*.tsx');

/**
 * Ubah nama halaman Inertia menjadi path file.
 *  - 'Attendance::Teacher/Index' → ../Modules/Attendance/Pages/Teacher/Index.tsx
 *  - 'settings/profile'          → ../pages/settings/profile.tsx (bawaan starter kit)
 */
export function pagePath(name: string): string {
    const separator = name.indexOf('::');

    if (separator === -1) {
        return `../pages/${name}.tsx`;
    }

    const module = name.slice(0, separator);
    const path = name.slice(separator + 2);

    return `../Modules/${module}/Pages/${path}.tsx`;
}

export function resolvePage(name: string): Promise<PageModule> {
    const path = pagePath(name);
    const loader: PageLoader | undefined =
        modulePages[path] ?? legacyPages[path];

    if (!loader) {
        throw new Error(`Halaman Inertia tidak ditemukan: ${name} (${path})`);
    }

    return loader();
}
