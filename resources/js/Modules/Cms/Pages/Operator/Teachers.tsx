import { Head, router } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { toastFirstError } from '@/lib/toast-errors';
import type { TeacherWebsiteRow } from '@/Modules/Cms/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    teachers: Paginated<TeacherWebsiteRow>;
    filters: { q?: string; tampil?: string };
    shownCount: number;
};

const BASE = '/operator/cms/guru';

const SHOWN_OPTIONS = [
    { value: 'ya', label: 'Tampil' },
    { value: 'tidak', label: 'Tidak tampil' },
];

function save(teacher: TeacherWebsiteRow, show: boolean, title: string) {
    router.put(
        `${BASE}/${teacher.public_id}`,
        { show_on_website: show, website_title: title },
        {
            preserveScroll: true,
            onError: (errors) => toastFirstError(errors),
        },
    );
}

function TeacherRow({ teacher }: { teacher: TeacherWebsiteRow }) {
    const [title, setTitle] = useState(teacher.website_title ?? '');

    return (
        <li className="flex flex-wrap items-center gap-3 px-4 py-3 sm:flex-nowrap sm:px-5">
            <label className="flex min-w-0 flex-1 items-center gap-3">
                <Checkbox
                    checked={teacher.show_on_website}
                    onCheckedChange={(c) => save(teacher, c === true, title)}
                    aria-label={`Tampilkan ${teacher.name} di website`}
                />
                <PersonAvatar
                    name={teacher.name}
                    photoUrl={teacher.photo_url}
                    className="size-10"
                />
                <span className="min-w-0 truncate font-medium text-slate-900">
                    {teacher.name}
                </span>
            </label>
            <Input
                value={title}
                maxLength={80}
                onChange={(e) => setTitle(e.target.value)}
                onBlur={() => {
                    if (title !== (teacher.website_title ?? '')) {
                        save(teacher, teacher.show_on_website, title);
                    }
                }}
                placeholder="Jabatan, mis. Guru Kelas 1A"
                aria-label={`Jabatan ${teacher.name} di website`}
                className="w-full sm:w-64"
            />
        </li>
    );
}

export default function CmsTeachers({ teachers, filters, shownCount }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { q: filters.q || undefined, tampil: filters.tampil || undefined },
        { only: ['teachers', 'filters'] },
    );
    const loading = useInertiaLoading();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Website', href: '/operator/cms/halaman' },
                { title: 'Guru', href: BASE },
            ]}
        >
            <Head title="Guru di website" />
            <PageHeader
                title="Guru di website"
                description={`Pilih guru yang tampil di halaman Guru & Tenaga Kependidikan. Nama dan foto mengikuti data guru. ${shownCount} guru ditampilkan.`}
            />
            <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="border-b border-slate-200 p-4">
                    <FilterBar
                        search={values.q}
                        onSearchChange={(value) => set('q', value, true)}
                        searchPlaceholder="Cari nama guru…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <SelectFilter
                            label="Filter tampil"
                            allLabel="Semua guru"
                            value={values.tampil}
                            options={SHOWN_OPTIONS}
                            onChange={(v) => set('tampil', v)}
                            className="w-40"
                        />
                    </FilterBar>
                </div>
                {loading ? (
                    <ListSkeleton rows={6} />
                ) : teachers.data.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="Guru tidak ditemukan"
                        description={
                            isActive
                                ? 'Coba ubah kata kunci atau filter.'
                                : 'Tambahkan data guru aktif di menu Guru.'
                        }
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {teachers.data.map((teacher) => (
                            <TeacherRow
                                key={`${teacher.public_id}-${teacher.website_title ?? ''}`}
                                teacher={teacher}
                            />
                        ))}
                    </ul>
                )}
                <Pagination
                    paginated={teachers}
                    only={['teachers', 'filters']}
                />
            </section>
        </AppLayout>
    );
}
