import { Head, Link } from '@inertiajs/react';
import { HeartPulse, Plus } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Pagination } from '@/Components/shared/Pagination';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import { Button } from '@/Components/ui/button';
import ParentLayout from '@/Layouts/ParentLayout';
import { ConditionListItem } from '@/Modules/ChildCondition/Components/ConditionListItem';
import type { ConditionRow } from '@/Modules/ChildCondition/types';
import type { Paginated } from '@/types';

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    conditions: Paginated<ConditionRow>;
};

export default function GuardianConditionIndex({
    childOptions,
    activeChild,
    conditions,
}: Props) {
    const createHref = activeChild
        ? `/orang-tua/kondisi-anak/lapor?anak=${activeChild}`
        : '/orang-tua/kondisi-anak/lapor';

    return (
        <ParentLayout>
            <Head title="Kondisi Anak" />

            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Kondisi Anak
                    </h1>
                    <p className="text-sm text-slate-500">
                        Kabari guru bila anak sakit, izin, atau butuh perhatian
                        khusus.
                    </p>
                </div>
                {childOptions.length > 0 && (
                    <Button asChild>
                        <Link href={createHref}>
                            <Plus />
                            Laporkan kondisi
                        </Link>
                    </Button>
                )}
            </div>

            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {conditions.data.length === 0 ? (
                    <EmptyState
                        icon={HeartPulse}
                        title="Belum ada laporan"
                        description="Laporan yang Anda kirim beserta tanggapan guru akan tampil di sini."
                    />
                ) : (
                    <>
                        <ul className="divide-y divide-slate-100">
                            {conditions.data.map((condition) => (
                                <li key={condition.public_id}>
                                    <ConditionListItem
                                        href={`/orang-tua/kondisi-anak/${condition.public_id}`}
                                        condition={condition}
                                    />
                                </li>
                            ))}
                        </ul>
                        <div className="border-t border-slate-100 px-4 py-3">
                            <Pagination paginated={conditions} />
                        </div>
                    </>
                )}
            </div>
        </ParentLayout>
    );
}
