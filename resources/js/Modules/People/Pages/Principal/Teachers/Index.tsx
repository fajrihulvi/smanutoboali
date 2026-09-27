import { Head } from '@inertiajs/react';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { TeacherTable } from '@/Modules/People/Components/TeacherTable';
import type { Option, TeacherRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    teachers: Paginated<TeacherRow>;
    filters: { search?: string; status?: string; employment?: string };
    employment: Option[];
};

export default function PrincipalTeachersIndex({
    teachers,
    filters,
    employment,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            search: filters.search,
            status: filters.status,
            employment: filters.employment,
        },
        { only: ['teachers', 'filters'] },
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Monitoring Guru', href: '/kepala-sekolah/guru' },
            ]}
        >
            <Head title="Monitoring Guru" />

            <PageHeader
                title="Monitoring Guru"
                description="Daftar guru beserta status kepegawaiannya (hanya lihat)."
            />

            <TeacherTable
                teachers={teachers}
                filtered={isActive}
                readOnly
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama atau NIP…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter status kepegawaian"
                            allLabel="Semua kepegawaian"
                            value={values.employment}
                            options={employment}
                            onChange={(value) => set('employment', value)}
                            className="w-52"
                        />
                        <SelectFilter
                            label="Filter status"
                            allLabel="Semua status"
                            value={values.status}
                            options={[
                                { value: 'active', label: 'Aktif' },
                                { value: 'inactive', label: 'Nonaktif' },
                            ]}
                            onChange={(value) => set('status', value)}
                            className="w-36"
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
