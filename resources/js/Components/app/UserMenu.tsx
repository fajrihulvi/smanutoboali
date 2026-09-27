import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, UserRoundCog } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useInitials } from '@/hooks/use-initials';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';

export function UserMenu() {
    const { auth } = usePage().props;
    const getInitials = useInitials();
    const cleanup = useMobileNavigation();

    if (!auth.user) {
        return null;
    }

    const logout = () => {
        cleanup();
        router.flushAll();
        router.post('/logout');
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className="rounded-full transition-shadow duration-150 hover:ring-2 hover:ring-brand-100"
                aria-label="Menu akun"
                data-test="user-menu-button"
            >
                <Avatar className="size-9">
                    {auth.user.avatar && (
                        <AvatarImage
                            src={auth.user.avatar}
                            alt={auth.user.name}
                        />
                    )}
                    <AvatarFallback className="bg-brand-100 text-xs font-semibold text-brand-700">
                        {getInitials(auth.user.name)}
                    </AvatarFallback>
                </Avatar>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-60">
                <DropdownMenuLabel className="font-normal">
                    <p className="truncate text-sm font-semibold text-slate-900">
                        {auth.user.name}
                    </p>
                    <p className="truncate text-xs text-slate-500">
                        {auth.user.email}
                    </p>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link
                        href="/settings/profile"
                        onClick={cleanup}
                        className="cursor-pointer"
                    >
                        <UserRoundCog />
                        Profil &amp; keamanan
                    </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    onSelect={logout}
                    className="cursor-pointer text-rose-600 focus:bg-rose-50 focus:text-rose-700"
                    data-test="logout-button"
                >
                    <LogOut className="text-current" />
                    Keluar
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
