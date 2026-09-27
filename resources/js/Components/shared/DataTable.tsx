import { flexRender, tableFeatures, useTable } from '@tanstack/react-table';
import type { ColumnDef, RowData } from '@tanstack/react-table';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Pagination } from '@/Components/shared/Pagination';
import { TableSkeleton } from '@/Components/shared/Skeletons';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

/** Tanpa fitur klien: sort/filter/paginasi dikerjakan server (Laravel). */
const features = tableFeatures({});

export type DataTableColumn<T extends RowData> = ColumnDef<typeof features, T>;

type Props<T extends RowData> = {
    columns: DataTableColumn<T>[];
    data: Paginated<T>;
    getRowId: (row: T) => string;
    /** Prop Inertia untuk partial reload saat berpindah halaman. */
    only?: string[];
    toolbar?: ReactNode;
    empty?: {
        title: string;
        description?: string;
        icon?: LucideIcon;
        action?: ReactNode;
    };
    /** Paksa skeleton (mis. data dimuat lazy). */
    loading?: boolean;
};

/**
 * Tabel data dengan paginasi server-side Laravel (TanStack Table, manual pagination).
 * Menampilkan skeleton saat kunjungan Inertia berjalan dan empty state bila kosong.
 */
export function DataTable<T extends RowData>({
    columns,
    data,
    getRowId,
    only,
    toolbar,
    empty = { title: 'Belum ada data' },
    loading,
}: Props<T>) {
    const navigating = useInertiaLoading();
    const isLoading = loading ?? navigating;

    const table = useTable({
        features,
        data: data.data,
        columns,
        getRowId: (row) => getRowId(row),
    });

    const rows = table.getRowModel().rows;

    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {toolbar && (
                <div className="border-b border-slate-200 p-4">{toolbar}</div>
            )}

            {isLoading && rows.length === 0 ? (
                <TableSkeleton columns={Math.min(columns.length, 5)} />
            ) : rows.length === 0 ? (
                <EmptyState {...empty} />
            ) : (
                <div
                    className={cn(
                        'transition-opacity duration-150',
                        isLoading && 'pointer-events-none opacity-60',
                    )}
                    aria-busy={isLoading}
                >
                    <Table>
                        <TableHeader>
                            {table.getHeaderGroups().map((group) => (
                                <TableRow
                                    key={group.id}
                                    className="hover:bg-transparent"
                                >
                                    {group.headers.map((header) => (
                                        <TableHead
                                            key={header.id}
                                            className={
                                                header.column.columnDef.meta
                                                    ?.className
                                            }
                                        >
                                            {header.isPlaceholder
                                                ? null
                                                : flexRender(
                                                      header.column.columnDef
                                                          .header,
                                                      header.getContext(),
                                                  )}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            ))}
                        </TableHeader>
                        <TableBody>
                            {rows.map((row) => (
                                <TableRow key={row.id}>
                                    {row.getAllCells().map((cell) => (
                                        <TableCell
                                            key={cell.id}
                                            className={
                                                cell.column.columnDef.meta
                                                    ?.className
                                            }
                                        >
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            )}

            <Pagination paginated={data} only={only} />
        </div>
    );
}
