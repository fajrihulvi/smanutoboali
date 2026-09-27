import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/Components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/Components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { MenuItem } from '@/types';

/** Item aktif: latar white/10 + garis kiri brand-500 (§6.3). */
const activeClass =
    'data-[active=true]:shadow-[inset_3px_0_0_var(--color-brand-500)] data-[active=true]:font-semibold';

export function NavMenu({ items }: { items: MenuItem[] }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const isActive = (item: MenuItem) =>
        item.href.endsWith('/dashboard')
            ? isCurrentUrl(item.href)
            : isCurrentOrParentUrl(item.href);

    return (
        <SidebarGroup>
            <SidebarGroupLabel className="tracking-wider text-white/40 uppercase">
                Menu
            </SidebarGroupLabel>
            <SidebarMenu className="gap-0.5">
                {items.map((item) =>
                    item.children ? (
                        <Collapsible
                            key={item.href}
                            asChild
                            defaultOpen={isCurrentOrParentUrl(item.href)}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        tooltip={item.label}
                                        isActive={isCurrentOrParentUrl(
                                            item.href,
                                        )}
                                        className={activeClass}
                                    >
                                        <item.icon />
                                        <span>{item.label}</span>
                                        <ChevronRight className="ml-auto transition-transform duration-150 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub className="border-white/10">
                                        {item.children.map((child) => (
                                            <SidebarMenuSubItem
                                                key={child.href}
                                            >
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={isCurrentOrParentUrl(
                                                        child.href,
                                                    )}
                                                    className="text-white/70 hover:text-white data-[active=true]:bg-white/10 data-[active=true]:text-white"
                                                >
                                                    <Link
                                                        href={child.href}
                                                        prefetch
                                                    >
                                                        <span>
                                                            {child.label}
                                                        </span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    ) : (
                        <SidebarMenuItem key={item.href}>
                            <SidebarMenuButton
                                asChild
                                isActive={isActive(item)}
                                tooltip={item.label}
                                className={activeClass}
                            >
                                <Link href={item.href} prefetch>
                                    <item.icon />
                                    <span>{item.label}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ),
                )}
            </SidebarMenu>
        </SidebarGroup>
    );
}
