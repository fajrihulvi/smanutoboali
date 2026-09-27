import { Head } from '@inertiajs/react';
import { CalendarCheck2, NotebookPen, Wallet } from 'lucide-react';
import { useState } from 'react';
import { StatCard } from '@/Components/shared/StatCard';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';

const children = [
    { id: 'a', name: 'Aisyah', initials: 'AI', className: 'VII-A' },
    { id: 'b', name: 'Bima', initials: 'BI', className: 'IV-B' },
];

export default function StyleGuideParent() {
    const [active, setActive] = useState(children[0].id);
    const child = children.find((item) => item.id === active) ?? children[0];

    return (
        <ParentLayout
            header={
                <div className="flex gap-2 overflow-x-auto">
                    {children.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => setActive(item.id)}
                            className={cn(
                                'flex shrink-0 items-center gap-2 rounded-full border py-1 pr-3 pl-1 text-sm font-medium transition-colors duration-150',
                                item.id === active
                                    ? 'border-brand-200 bg-brand-50 text-brand-700'
                                    : 'border-slate-200 bg-white text-slate-600',
                            )}
                        >
                            <span className="flex size-7 items-center justify-center rounded-full bg-brand-600 text-[11px] font-semibold text-white">
                                {item.initials}
                            </span>
                            {item.name}
                        </button>
                    ))}
                </div>
            }
        >
            <Head title="Pratinjau Portal Orang Tua" />

            <div>
                <p className="text-sm text-slate-500">Selamat pagi,</p>
                <h1 className="text-xl font-semibold text-slate-900">
                    Perkembangan {child.name} · {child.className}
                </h1>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div className="flex items-center justify-between">
                    <p className="text-sm font-medium text-slate-600">
                        Kehadiran hari ini
                    </p>
                    <StatusBadge variant="success" dot>
                        Hadir
                    </StatusBadge>
                </div>
                <p className="mt-2 text-xs text-slate-500">
                    Tercatat oleh wali kelas pukul 07.12
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard
                    label="Kehadiran bulan ini"
                    value="95%"
                    icon={CalendarCheck2}
                />
                <StatCard
                    label="Nilai terbaru"
                    value="88"
                    icon={NotebookPen}
                    hint="Matematika · UH 2"
                />
                <StatCard
                    label="Tagihan jatuh tempo"
                    value={formatCurrency(350000)}
                    icon={Wallet}
                    hint="10 Okt 2026"
                />
            </div>
        </ParentLayout>
    );
}
