import { Link } from '@inertiajs/react';
import { HeartPulse } from 'lucide-react';
import { ConditionListItem } from '@/Modules/ChildCondition/Components/ConditionListItem';
import type { ConditionRow } from '@/Modules/ChildCondition/types';

/**
 * Widget dashboard guru: laporan kondisi anak yang berlaku hari ini.
 */
export function TodayConditionsCard({ items }: { items: ConditionRow[] }) {
    return (
        <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <header className="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-3">
                <h2 className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <HeartPulse className="size-4 text-brand-600" />
                    Kondisi Anak Hari Ini
                </h2>
                <Link
                    href="/guru/kondisi-anak"
                    className="text-sm font-medium text-brand-600 hover:text-brand-700"
                >
                    Lihat semua
                </Link>
            </header>
            {items.length === 0 ? (
                <p className="px-4 py-6 text-center text-sm text-slate-500">
                    Tidak ada laporan kondisi anak untuk hari ini.
                </p>
            ) : (
                <ul className="divide-y divide-slate-100">
                    {items.map((item) => (
                        <li key={item.public_id}>
                            <ConditionListItem
                                href={`/guru/kondisi-anak/${item.public_id}`}
                                condition={item}
                                staff
                            />
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
