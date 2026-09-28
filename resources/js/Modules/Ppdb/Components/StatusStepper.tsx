import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { ApplicantRow } from '@/Modules/Ppdb/types';

const STEPS = [
    'Formulir',
    'Terkirim',
    'Verifikasi',
    'Seleksi',
    'Hasil',
    'Daftar ulang',
];

/**
 * Tahap pendaftaran PPDB. Tahap "Hasil" merah bila tidak diterima, kuning bila perlu perbaikan.
 */
export function StatusStepper({
    applicant,
}: {
    applicant: Pick<ApplicantRow, 'step' | 'status'>;
}) {
    const rejected = applicant.status.value === 'REJECTED';
    const revision = applicant.status.value === 'NEEDS_REVISION';

    return (
        <ol className="flex w-full items-start" aria-label="Tahap pendaftaran">
            {STEPS.map((label, index) => {
                const done =
                    index < applicant.step ||
                    applicant.status.value === 'RE_REGISTERED';
                const current =
                    index === applicant.step &&
                    applicant.status.value !== 'RE_REGISTERED';

                return (
                    <li
                        key={label}
                        className="flex min-w-0 flex-1 flex-col items-center gap-1 text-center"
                    >
                        <div className="flex w-full items-center">
                            <span
                                className={cn(
                                    'h-0.5 flex-1',
                                    index === 0
                                        ? 'bg-transparent'
                                        : done || current
                                          ? 'bg-brand-500'
                                          : 'bg-slate-200',
                                )}
                            />
                            <span
                                className={cn(
                                    'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                    done && 'bg-brand-600 text-white',
                                    current &&
                                        !rejected &&
                                        !revision &&
                                        'bg-brand-100 text-brand-700 ring-2 ring-brand-500',
                                    current &&
                                        revision &&
                                        'bg-amber-100 text-amber-700 ring-2 ring-amber-500',
                                    current &&
                                        rejected &&
                                        'bg-rose-100 text-rose-700 ring-2 ring-rose-500',
                                    !done &&
                                        !current &&
                                        'bg-slate-100 text-slate-400',
                                )}
                                aria-current={current ? 'step' : undefined}
                            >
                                {done ? (
                                    <Check className="size-4" />
                                ) : (
                                    index + 1
                                )}
                            </span>
                            <span
                                className={cn(
                                    'h-0.5 flex-1',
                                    index === STEPS.length - 1
                                        ? 'bg-transparent'
                                        : done
                                          ? 'bg-brand-500'
                                          : 'bg-slate-200',
                                )}
                            />
                        </div>
                        <span
                            className={cn(
                                'px-0.5 text-[10px] leading-tight sm:text-xs',
                                current
                                    ? 'font-semibold text-slate-900'
                                    : 'text-slate-500',
                            )}
                        >
                            {label}
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
