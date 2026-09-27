import { Head, Link } from '@inertiajs/react';
import { FileSpreadsheet, Plus } from 'lucide-react';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { StudentTable } from '@/Modules/People/Components/StudentTable';
import type { Option, StudentRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    students: Paginated<StudentRow>;
    filters: { search?: string; status?: string; gender?: string };
    options: { genders: Option[]; statuses: Option[] };
};

const BASE = '/operator/master-data/siswa';

export default function StudentsIndex({ students, filters, options }: Props) {
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
                { title: 'Master Data', href: BASE },
                { title: 'Siswa', href: BASE },
            ]}
        >
            <Head title="Data Siswa" />

            <PageHeader
                title="Data Siswa"
                description="Data induk siswa dan hubungan dengan orang tua/wali."
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href={`${BASE}/impor`}>
                                <FileSpreadsheet />
                                Impor Excel
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={`${BASE}/tambah`}>
                                <Plus />
                                Tambah siswa
                            </Link>
                        </Button>
                    </>
                }
            />

            <StudentTable
                students={students}
                filtered={isActive}
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
                            options={options.statuses}
                            onChange={(value) => set('status', value)}
                            className="w-36"
                        />
                        <SelectFilter
                            label="Filter jenis kelamin"
                            allLabel="L & P"
                            value={values.gender}
                            options={options.genders}
                            onChange={(value) => set('gender', value)}
                            className="w-36"
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
