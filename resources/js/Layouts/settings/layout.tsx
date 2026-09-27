import { Link } from '@inertiajs/react';
import { KeyRound, UserRound } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { PageHeader } from '@/Components/shared/PageHeader';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';

const items = [
    { title: 'Profil', href: '/settings/profile', icon: UserRound },
    { title: 'Keamanan', href: '/settings/security', icon: KeyRound },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="space-y-6">
            <PageHeader
                title="Pengaturan Akun"
                description="Kelola profil dan keamanan akun Anda."
            />

            <div className="flex flex-col gap-6 lg:flex-row">
                <nav
                    className="flex gap-1 lg:w-52 lg:flex-col"
                    aria-label="Pengaturan akun"
                >
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium transition-colors duration-150',
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-brand-50 text-brand-700'
                                    : 'text-slate-600 hover:bg-white hover:text-slate-900',
                            )}
                        >
                            <item.icon className="size-4" />
                            {item.title}
                        </Link>
                    ))}
                </nav>

                <section className="flex-1 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:max-w-2xl">
                    {children}
                </section>
            </div>
        </div>
    );
}
