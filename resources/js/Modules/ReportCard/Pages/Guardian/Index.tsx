import { Head, Link } from '@inertiajs/react';
import { Baby, ChevronRight, Download, FileText } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import { Button } from '@/Components/ui/button';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    child: { public_id: string; name: string } | null;
    cards: {
        public_id: string;
        period: string;
        classroom: string;
        published_at: string | null;
    }[];
};

const BASE = '/orang-tua/raport';

export default function GuardianReportCards({ child, cards }: Props) {
    return (
        <ParentLayout>
            <Head title="Raport" />

            <div>
                <h1 className="text-xl font-semibold text-slate-900">Raport</h1>
                <p className="text-sm text-slate-500">
                    {child ? child.name : 'Raport yang sudah terbit.'}
                </p>
            </div>

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : cards.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={FileText}
                        title="Belum ada raport terbit"
                        description="Raport tampil di sini setelah sekolah menerbitkannya."
                    />
                </div>
            ) : (
                <ul className="space-y-3">
                    {cards.map((card) => (
                        <li
                            key={card.public_id}
                            className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <FileText className="size-5" />
                            </span>
                            <Link
                                href={`${BASE}/${card.public_id}`}
                                className="min-w-0 flex-1"
                            >
                                <p className="font-semibold text-slate-900">
                                    {card.period}
                                </p>
                                <p className="text-xs text-slate-500">
                                    Rombel {card.classroom}
                                    {card.published_at &&
                                        ` · terbit ${formatDate(card.published_at)}`}
                                </p>
                            </Link>
                            <Button variant="ghost" size="icon" asChild>
                                <a
                                    href={`${BASE}/${card.public_id}/pdf?unduh=1`}
                                    aria-label="Unduh PDF"
                                >
                                    <Download className="size-4" />
                                </a>
                            </Button>
                            <ChevronRight className="size-4 text-slate-400" />
                        </li>
                    ))}
                </ul>
            )}
        </ParentLayout>
    );
}
