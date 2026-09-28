import { Head, router, usePage } from '@inertiajs/react';
import { BellOff, CheckCheck } from 'lucide-react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDateTime, formatRelative } from '@/lib/format';
import { requestJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type NotificationItem = {
    id: string;
    title: string;
    body: string | null;
    url: string | null;
    read_at: string | null;
    created_at: string;
};

type Props = {
    notifications: Paginated<NotificationItem>;
    filters: { status?: string };
    unread: number;
};

const URL = '/notifikasi';

export default function NotificationsIndex({
    notifications,
    filters,
    unread,
}: Props) {
    const { auth } = usePage().props;
    const isGuardian = auth.activeRole === 'guardian';
    const unreadOnly = filters.status === 'belum';

    const open = async (item: NotificationItem) => {
        if (!item.read_at) {
            await requestJson(`${URL}/${item.id}/dibaca`, 'POST').catch(
                () => null,
            );
        }

        if (item.url) {
            router.visit(item.url);
        } else {
            router.reload({ only: ['notifications', 'unread'] });
        }
    };

    const markAll = () =>
        router.post(`${URL}/dibaca-semua`, {}, { preserveScroll: true });

    const tabs = (
        <div
            className="flex gap-1.5"
            role="tablist"
            aria-label="Filter notifikasi"
        >
            {[
                { label: 'Semua', value: undefined },
                {
                    label: `Belum dibaca${unread > 0 ? ` (${unread})` : ''}`,
                    value: 'belum',
                },
            ].map((tab) => {
                const active = (tab.value ?? '') === (filters.status ?? '');

                return (
                    <button
                        key={tab.label}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        onClick={() =>
                            router.get(
                                URL,
                                tab.value ? { status: tab.value } : {},
                                { preserveScroll: true },
                            )
                        }
                        className={cn(
                            'rounded-full border px-3.5 py-1.5 text-sm font-medium transition-colors duration-150',
                            active
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-brand-300',
                        )}
                    >
                        {tab.label}
                    </button>
                );
            })}
        </div>
    );

    const list: ReactNode =
        notifications.data.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <EmptyState
                    icon={BellOff}
                    title={
                        unreadOnly
                            ? 'Semua notifikasi sudah dibaca'
                            : 'Belum ada notifikasi'
                    }
                />
            </div>
        ) : (
            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <ul className="divide-y divide-slate-100">
                    {notifications.data.map((item) => (
                        <li key={item.id}>
                            <button
                                type="button"
                                onClick={() => void open(item)}
                                className={cn(
                                    'flex w-full items-start gap-3 px-4 py-3 text-left hover:bg-slate-50 sm:px-5',
                                    !item.read_at && 'bg-brand-50/40',
                                )}
                            >
                                <span
                                    className={cn(
                                        'mt-1.5 size-2 shrink-0 rounded-full',
                                        item.read_at
                                            ? 'bg-transparent'
                                            : 'bg-brand-500',
                                    )}
                                    aria-hidden
                                />
                                <span className="min-w-0 flex-1">
                                    <span
                                        className={cn(
                                            'block text-sm',
                                            item.read_at
                                                ? 'text-slate-700'
                                                : 'font-semibold text-slate-900',
                                        )}
                                    >
                                        {item.title}
                                    </span>
                                    {item.body && (
                                        <span className="mt-0.5 block text-sm text-slate-500">
                                            {item.body}
                                        </span>
                                    )}
                                    <span
                                        className="mt-1 block text-xs text-slate-400"
                                        title={formatDateTime(item.created_at)}
                                    >
                                        {formatRelative(item.created_at)}
                                    </span>
                                </span>
                            </button>
                        </li>
                    ))}
                </ul>
                <Pagination
                    paginated={notifications}
                    only={['notifications', 'filters', 'unread']}
                />
            </section>
        );

    const markAllButton = unread > 0 && (
        <Button variant="outline" size="sm" onClick={markAll}>
            <CheckCheck />
            Tandai semua dibaca
        </Button>
    );

    if (isGuardian) {
        return (
            <ParentLayout>
                <Head title="Notifikasi" />
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <h1 className="text-xl font-semibold text-slate-900">
                        Notifikasi
                    </h1>
                    {markAllButton}
                </div>
                {tabs}
                {list}
            </ParentLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={[{ title: 'Notifikasi', href: URL }]}>
            <Head title="Notifikasi" />
            <PageHeader
                title="Notifikasi"
                description="Semua pemberitahuan untuk akun Anda."
                actions={markAllButton}
            />
            <div className="mb-4">{tabs}</div>
            {list}
        </AppLayout>
    );
}
