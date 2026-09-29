import { formatRelative } from '@/lib/format';
import { PanelEmpty } from '@/Modules/Dashboard/Components/DashboardPanel';
import type { AuditItem } from '@/Modules/Dashboard/types';

export function AuditFeedList({ items }: { items: AuditItem[] }) {
    if (items.length === 0) {
        return <PanelEmpty>Belum ada aktivitas tercatat.</PanelEmpty>;
    }

    return (
        <ol className="relative space-y-4 border-l border-slate-200 pl-4">
            {items.map((item) => (
                <li key={item.key} className="relative">
                    <span className="absolute top-1.5 -left-[21px] size-2.5 rounded-full border-2 border-white bg-brand-500" />
                    <p className="text-sm break-words text-slate-800">
                        {item.description}
                    </p>
                    <p className="mt-0.5 text-xs text-slate-500">
                        {[
                            item.module,
                            item.causer,
                            formatRelative(item.created_at),
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </li>
            ))}
        </ol>
    );
}
