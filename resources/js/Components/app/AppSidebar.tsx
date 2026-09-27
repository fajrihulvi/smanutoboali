import { Link, usePage } from '@inertiajs/react';
import { AppLogo } from '@/Components/app/AppLogo';
import { NavMenu } from '@/Components/app/NavMenu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarRail,
} from '@/Components/ui/sidebar';
import { navigationFor } from '@/config/navigation';
import { portals } from '@/config/portals';

export function AppSidebar() {
    const { auth } = usePage().props;
    const items = navigationFor(auth.activeRole, auth.permissions);
    const home = items[0]?.href ?? '/';

    return (
        <Sidebar collapsible="icon" className="border-r-0">
            <SidebarHeader className="h-16 justify-center px-3">
                <Link href={home} className="flex items-center rounded-lg px-1">
                    <AppLogo className="group-data-[collapsible=icon]:[&>span:last-child]:hidden" />
                </Link>
            </SidebarHeader>

            <SidebarContent className="px-1 py-2">
                <NavMenu items={items} />
            </SidebarContent>

            {auth.activeRole && (
                <SidebarFooter className="px-4 pb-4 group-data-[collapsible=icon]:hidden">
                    <p className="rounded-xl bg-white/5 px-3 py-2 text-xs text-white/60">
                        {portals[auth.activeRole].title}
                    </p>
                </SidebarFooter>
            )}

            <SidebarRail />
        </Sidebar>
    );
}
