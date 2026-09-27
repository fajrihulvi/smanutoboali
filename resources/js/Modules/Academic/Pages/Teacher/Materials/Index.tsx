import { Head } from '@inertiajs/react';
import { BookOpen, Plus, UserRoundX } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { RowActions } from '@/Components/shared/RowActions';
import { ListSkeleton } from '@/Components/shared/Skeletons';
import { Button } from '@/Components/ui/button';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { useFilters } from '@/hooks/use-filters';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';
import AppLayout from '@/Layouts/AppLayout';
import { MaterialFormDialog } from '@/Modules/Academic/Components/MaterialFormDialog';
import { MaterialItem } from '@/Modules/Academic/Components/MaterialItem';
import type { MaterialRow, Option } from '@/Modules/Academic/types';
import { SelectFilter } from '@/Modules/People/Components/SelectFilter';
import type { Paginated } from '@/types';

type Props = {
    hasTeacherProfile: boolean;
    materials: Paginated<MaterialRow>;
    assignments: Option[];
    types: Option[];
    filters: { penugasan?: string; search?: string };
    maxUploadKb: number;
};

const URL = '/guru/akademik/materi';

export default function TeacherMaterialsIndex({
    hasTeacherProfile,
    materials,
    assignments,
    types,
    filters,
    maxUploadKb,
}: Props) {
    const { values, set, reset, isActive } = useFilters(
        { penugasan: filters.penugasan, search: filters.search },
        { only: ['materials', 'filters'] },
    );
    const loading = useInertiaLoading();
    const [dialog, setDialog] = useState<{
        open: boolean;
        material: MaterialRow | null;
    }>({
        open: false,
        material: null,
    });
    const { confirmDelete, dialog: deleteDialog } = useConfirmDelete();

    const canCreate = hasTeacherProfile && assignments.length > 0;

    return (
        <AppLayout breadcrumbs={[{ title: 'Materi', href: URL }]}>
            <Head title="Materi Pembelajaran" />

            <PageHeader
                title="Materi Pembelajaran"
                description="Bagikan file atau tautan materi untuk mapel yang Anda ampu. Orang tua siswa di rombel tersebut dapat melihatnya."
                actions={
                    canCreate && (
                        <Button
                            onClick={() =>
                                setDialog({ open: true, material: null })
                            }
                        >
                            <Plus />
                            Tambah materi
                        </Button>
                    )
                }
            />

            {!hasTeacherProfile ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Akun belum terhubung ke data guru"
                        description="Hubungi operator sekolah untuk menautkan akun Anda ke data guru."
                    />
                </div>
            ) : (
                <section className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 p-4">
                        <FilterBar
                            search={values.search}
                            onSearchChange={(value) =>
                                set('search', value, true)
                            }
                            searchPlaceholder="Cari judul materi…"
                            isActive={isActive}
                            onReset={reset}
                        >
                            <SelectFilter
                                label="Filter mapel"
                                allLabel="Semua mapel"
                                value={values.penugasan}
                                options={assignments}
                                onChange={(value) => set('penugasan', value)}
                                className="w-56"
                            />
                        </FilterBar>
                    </div>

                    {loading ? (
                        <ListSkeleton rows={4} />
                    ) : materials.data.length === 0 ? (
                        <EmptyState
                            icon={BookOpen}
                            title={
                                isActive
                                    ? 'Materi tidak ditemukan'
                                    : 'Belum ada materi'
                            }
                            description={
                                isActive
                                    ? 'Coba ubah kata kunci atau filter.'
                                    : assignments.length === 0
                                      ? 'Anda belum punya penugasan mengajar di tahun ajaran aktif.'
                                      : 'Tambahkan materi pertama Anda.'
                            }
                        />
                    ) : (
                        <ul className="divide-y divide-slate-100">
                            {materials.data.map((material) => (
                                <li
                                    key={material.public_id}
                                    className="p-4 sm:px-5"
                                >
                                    <MaterialItem
                                        material={material}
                                        showContext
                                        actions={
                                            <RowActions
                                                label={material.title}
                                                onEdit={() =>
                                                    setDialog({
                                                        open: true,
                                                        material,
                                                    })
                                                }
                                                onDelete={() =>
                                                    confirmDelete({
                                                        url: `${URL}/${material.public_id}`,
                                                        title: `Hapus materi "${material.title}"?`,
                                                        description:
                                                            'Materi dan file-nya tidak lagi bisa dibuka orang tua.',
                                                    })
                                                }
                                            />
                                        }
                                    />
                                </li>
                            ))}
                        </ul>
                    )}

                    <Pagination
                        paginated={materials}
                        only={['materials', 'filters']}
                    />
                </section>
            )}

            {canCreate && (
                <MaterialFormDialog
                    open={dialog.open}
                    onOpenChange={(open) =>
                        setDialog((current) => ({ ...current, open }))
                    }
                    material={dialog.material}
                    assignments={assignments}
                    types={types}
                    defaultAssignment={values.penugasan}
                    maxUploadKb={maxUploadKb}
                />
            )}
            {deleteDialog}
        </AppLayout>
    );
}
