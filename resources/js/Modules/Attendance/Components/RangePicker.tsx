import { router } from '@inertiajs/react';
import { Input } from '@/Components/ui/input';

type Props = { from: string; to: string };

/**
 * Rentang tanggal lewat query `?dari=&sampai=`.
 */
export function RangePicker({ from, to }: Props) {
    const today = new Date().toLocaleDateString('en-CA');
    const go = (next: { dari: string; sampai: string }) =>
        router.get(window.location.pathname, next, { preserveScroll: true });

    return (
        <div className="grid w-full grid-cols-2 items-end gap-2 sm:flex sm:w-auto">
            <div className="grid min-w-0 gap-1">
                <label
                    htmlFor="range-from"
                    className="text-xs font-medium text-slate-500"
                >
                    Dari
                </label>
                <Input
                    id="range-from"
                    type="date"
                    max={to}
                    value={from}
                    onChange={(event) =>
                        event.target.value &&
                        go({ dari: event.target.value, sampai: to })
                    }
                    className="w-full min-w-0 sm:w-40"
                />
            </div>
            <div className="grid min-w-0 gap-1">
                <label
                    htmlFor="range-to"
                    className="text-xs font-medium text-slate-500"
                >
                    Sampai
                </label>
                <Input
                    id="range-to"
                    type="date"
                    min={from}
                    max={today}
                    value={to}
                    onChange={(event) =>
                        event.target.value &&
                        go({ dari: from, sampai: event.target.value })
                    }
                    className="w-full min-w-0 sm:w-40"
                />
            </div>
        </div>
    );
}
