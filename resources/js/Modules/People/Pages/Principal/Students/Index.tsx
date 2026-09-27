import { Head } from '@inertiajs/react';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { StudentTable } from '@/Modules/People/Components/StudentTable';
import type { Option, StudentRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    students: Paginated<StudentRow>;
    filters: { search?: string; status?: string; gender?: string };
    statuses: Option[];
    genders: Option[];
};

export default function PrincipalStudentsIndex({
    students,
    filters,
    statuses,
    genders,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            search: filters.search,
            status: filters.status,
            gender: filters.gender,
        },
        { only: ['students', 'filters'] },
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Monitoring Siswa', href: '/kepala-sekolah/siswa' },
            ]}
        >
            <Head title="Monitoring Siswa" />

            <PageHeader
                title="Monitoring Siswa"
                description="Daftar seluruh siswa (hanya lihat)."
            />

            <StudentTable
                students={students}
                filtered={isActive}
                readOnly
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama, NIS, NISN…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter status siswa"
                            allLabel="Semua status"
                            value={values.status}
                            options={statuses}
                            onChange={(value) => set('status', value)}
                            className="w-36"
                        />
                        <SelectFilter
                            label="Filter jenis kelamin"
                            allLabel="L & P"
                            value={values.gender}
                            options={genders}
                            onChange={(value) => set('gender', value)}
                            className="w-36"
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
