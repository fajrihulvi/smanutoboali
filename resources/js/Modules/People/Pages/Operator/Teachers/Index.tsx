import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import { TeacherTable } from '@/Modules/People/Components/TeacherTable';
import type { Option, TeacherRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    teachers: Paginated<TeacherRow>;
    filters: {
        search?: string;
        status?: string;
        employment?: string;
        account?: string;
    };
    options: { genders: Option[]; employment: Option[] };
};

export default function TeachersIndex({ teachers, filters, options }: Props) {
    const { values, set, reset, isActive } = useFilters(
        {
            search: filters.search,
            status: filters.status,
            employment: filters.employment,
            account: filters.account,
        },
        { only: ['teachers', 'filters'] },
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: '/operator/master-data/guru' },
                { title: 'Guru', href: '/operator/master-data/guru' },
            ]}
        >
            <Head title="Data Guru" />

            <PageHeader
                title="Data Guru"
                description="Kelola data induk guru dan akun login mereka."
                actions={
                    <Button asChild>
                        <Link href="/operator/master-data/guru/tambah">
                            <Plus />
                            Tambah guru
                        </Link>
                    </Button>
                }
            />

            <TeacherTable
                teachers={teachers}
                filtered={isActive}
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama, NIP, NUPTK, telepon…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter status kepegawaian"
                            allLabel="Semua kepegawaian"
                            value={values.employment}
                            options={options.employment}
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
                        <SelectFilter
                            label="Filter akun login"
                            allLabel="Semua akun"
                            value={values.account}
                            options={[
                                { value: 'yes', label: 'Punya akun' },
                                { value: 'no', label: 'Belum punya akun' },
                            ]}
                            onChange={(value) => set('account', value)}
                        />
                    </FilterBar>
                }
            />
        </AppLayout>
    );
}
