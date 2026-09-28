import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { AppLogo } from '@/Components/app/AppLogo';
import { formatBadge } from '@/Components/app/NavMenu';
import { NotificationBell } from '@/Components/app/NotificationBell';
import { RealtimeBridge } from '@/Components/app/RealtimeBridge';
import { RoleSwitcher } from '@/Components/app/RoleSwitcher';
import { UserMenu } from '@/Components/app/UserMenu';
import { FlashToaster } from '@/Components/shared/FlashToaster';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/Components/ui/sheet';
import { guardianBottomNav, navigationFor } from '@/config/navigation';
import { useChatUnread } from '@/hooks/use-chat-unread';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import type { MenuItem } from '@/types';

type Props = {
    children: ReactNode;
    /** Slot di bawah header, mis. StudentSwitcher (chip avatar anak). */
    header?: ReactNode;
};

/**
 * Portal Orang Tua — mobile-first: header ringkas + bottom navigation.
 * Di layar ≥ md, navigasi pindah ke header.
 */
export default function ParentLayout({ children, header }: Props) {
    const { auth } = usePage().props;
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const items = navigationFor('guardian', auth.permissions);
    const bottomItems = guardianBottomNav
        .map((href) => items.find((item) => item.href === href))
        .filter((item) => item !== undefined);

    const chatUnread = useChatUnread();
    const badgeOf = (item: MenuItem) =>
        item.badge === 'chat' && chatUnread > 0
            ? formatBadge(chatUnread)
            : null;

    const isActive = (href: string) =>
        href.endsWith('/dashboard')
            ? isCurrentUrl(href)
            : isCurrentOrParentUrl(href);

    return (
        <div className="min-h-dvh bg-slate-50 pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:pb-0">
            <header className="sticky top-0 z-30 bg-gradient-to-r from-brand-950 to-brand-900 text-white shadow-sm">
                <div className="mx-auto flex h-14 max-w-5xl items-center gap-2 px-4">
                    <Sheet>
                        <SheetTrigger
                            className="-ml-1 rounded-lg p-2 text-white/80 transition-colors duration-150 hover:bg-white/10 hover:text-white md:hidden"
                            aria-label="Buka menu"
                        >
                            <Menu className="size-5" />
                        </SheetTrigger>
                        <SheetContent
                            side="left"
                            className="w-72 border-r-0 bg-white p-0"
                        >
                            <SheetHeader className="border-b border-slate-200 px-5 py-4">
                                <SheetTitle>
                                    <AppLogo tone="dark" />
                                </SheetTitle>
                            </SheetHeader>
                            <nav className="flex flex-col gap-0.5 p-3">
                                {items.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={cn(
                                            'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors duration-150',
                                            isActive(item.href)
                                                ? 'bg-brand-50 text-brand-700'
                                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900',
                                        )}
                                    >
                                        <item.icon className="size-5" />
                                        {item.label}
                                        {badgeOf(item) && (
                                            <span className="ml-auto rounded-full bg-brand-600 px-1.5 text-xs font-semibold text-white">
                                                {badgeOf(item)}
                                            </span>
                                        )}
                                    </Link>
                                ))}
                            </nav>
                        </SheetContent>
                    </Sheet>

                    <Link
                        href="/orang-tua/dashboard"
                        className="flex items-center"
                    >
                        <AppLogo showText={false} className="sm:hidden" />
                        <AppLogo className="hidden sm:flex" />
                    </Link>

                    <nav className="ml-6 hidden items-center gap-1 md:flex">
                        {items.slice(0, 7).map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors duration-150',
                                    isActive(item.href)
                                        ? 'bg-white/15 text-white'
                                        : 'text-white/70 hover:bg-white/10 hover:text-white',
                                )}
                            >
                                {item.label}
                                {badgeOf(item) && (
                                    <span className="ml-1.5 rounded-full bg-brand-500 px-1.5 text-xs font-semibold text-white">
                                        {badgeOf(item)}
                                    </span>
                                )}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-1.5">
                        <RoleSwitcher className="border-white/20 bg-white/10 text-white hover:bg-white/20" />
                        <NotificationBell className="text-white/80 hover:bg-white/10 hover:text-white" />
                        <UserMenu />
                    </div>
                </div>
            </header>

            {header && (
                <div className="border-b border-slate-200 bg-white">
                    <div className="mx-auto max-w-5xl px-4 py-3">{header}</div>
                </div>
            )}

            <main className="mx-auto w-full max-w-5xl space-y-5 px-4 py-5 md:py-8">
                {children}
            </main>

            {/* Bottom navigation — mobile */}
            <nav
                className="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
                aria-label="Navigasi utama"
            >
                <ul className="grid grid-cols-5">
                    {bottomItems.map((item) => {
                        const active = isActive(item.href);

                        return (
                            <li key={item.href}>
                                <Link
                                    href={item.href}
                                    className={cn(
                                        'flex h-[4.5rem] flex-col items-center justify-center gap-1 text-[11px] font-medium transition-colors duration-150',
                                        active
                                            ? 'text-brand-600'
                                            : 'text-slate-500',
                                    )}
                                    aria-current={active ? 'page' : undefined}
                                >
                                    <span
                                        className={cn(
                                            'relative flex h-7 w-12 items-center justify-center rounded-full transition-colors duration-150',
                                            active && 'bg-brand-50',
                                        )}
                                    >
                                        <item.icon className="size-5" />
                                        {badgeOf(item) && (
                                            <span
                                                className="absolute -top-1 right-1 min-w-4 rounded-full bg-rose-500 px-1 text-center text-[10px] leading-4 font-semibold text-white"
                                                aria-label={`${badgeOf(item)} belum dibaca`}
                                            >
                                                {badgeOf(item)}
                                            </span>
                                        )}
                                    </span>
                                    {item.label}
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <FlashToaster />
            <RealtimeBridge />
        </div>
    );
}
