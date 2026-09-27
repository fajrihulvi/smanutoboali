import { Skeleton } from '@/Components/ui/skeleton';

export function TableSkeleton({
    rows = 5,
    columns = 4,
}: {
    rows?: number;
    columns?: number;
}) {
    return (
        <div
            className="divide-y divide-slate-100"
            aria-busy="true"
            aria-label="Memuat data"
        >
            {Array.from({ length: rows }).map((_, row) => (
                <div key={row} className="flex items-center gap-4 px-4 py-3.5">
                    {Array.from({ length: columns }).map((_, column) => (
                        <Skeleton
                            key={column}
                            className={
                                column === 0 ? 'h-4 flex-[2]' : 'h-4 flex-1'
                            }
                        />
                    ))}
                </div>
            ))}
        </div>
    );
}

export function CardSkeleton() {
    return (
        <div
            className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            aria-busy="true"
        >
            <div className="flex items-start justify-between">
                <Skeleton className="h-4 w-24" />
                <Skeleton className="size-10 rounded-full" />
            </div>
            <Skeleton className="mt-3 h-8 w-20" />
            <Skeleton className="mt-3 h-3 w-32" />
        </div>
    );
}
