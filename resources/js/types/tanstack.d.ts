/* eslint-disable @typescript-eslint/no-unused-vars -- parameter wajib sama dengan deklarasi asli */
import type { CellData, RowData, TableFeatures } from '@tanstack/table-core';

declare module '@tanstack/table-core' {
    interface ColumnMeta<
        in out TFeatures extends TableFeatures,
        in out TData extends RowData,
        TValue extends CellData = CellData,
    > {
        /** Kelas tambahan untuk <th> dan <td> kolom ini. */
        className?: string;
    }
}
