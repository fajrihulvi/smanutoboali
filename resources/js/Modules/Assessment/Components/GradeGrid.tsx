import { Lock, PencilLine } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { KeyboardEvent, ReactNode } from 'react';
import { HttpError, sendJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import { formatScore, parseScore } from '@/Modules/Assessment/lib/score';
import type {
    CellResult,
    GradeComponentRow,
    GradebookStudent,
    PredicateValue,
} from '@/Modules/Assessment/types';

type CellStatus = 'saving' | 'saved' | 'error' | 'invalid';

export type SaveState = {
    saving: number;
    failed: number;
    lastError: string | null;
};

type Props = {
    components: GradeComponentRow[];
    students: GradebookStudent[];
    kkm: number;
    readOnly: boolean;
    /** Deskripsi terbaru per siswa (public_id), dikelola halaman. */
    descriptions: Record<string, string | null>;
    /** Header kolom komponen (menu aksi) — disediakan halaman. */
    renderComponentHeader: (component: GradeComponentRow) => ReactNode;
    onEditDescription: (student: GradebookStudent, result: CellResult) => void;
    onSaveStateChange: (state: SaveState) => void;
};

const DEBOUNCE_MS = 600;

const key = (student: string, component: string) => `${student}:${component}`;

/**
 * Spreadsheet nilai: satu input per sel, simpan otomatis per sel (debounce, flush saat blur),
 * navigasi Enter/↑/↓ dan ←/→ di tepi teks. Sel komponen terpublikasi terkunci.
 */
export function GradeGrid({
    components,
    students,
    kkm,
    readOnly,
    descriptions,
    renderComponentHeader,
    onEditDescription,
    onSaveStateChange,
}: Props) {
    const [values, setValues] = useState<Record<string, string>>(() => {
        const initial: Record<string, string> = {};
        students.forEach((student) =>
            components.forEach((component) => {
                initial[key(student.public_id, component.public_id)] =
                    formatScore(student.scores[component.public_id] ?? null);
            }),
        );

        return initial;
    });
    const [status, setStatus] = useState<Record<string, CellStatus>>({});
    const [results, setResults] = useState<Record<string, CellResult>>(() =>
        Object.fromEntries(
            students.map((student) => [
                student.public_id,
                {
                    final: student.final,
                    predicate: student.predicate,
                    suggested: student.suggested,
                },
            ]),
        ),
    );
    const timers = useRef<Record<string, ReturnType<typeof setTimeout>>>({});
    // Nilai terbaru dibaca lewat ref: callback setTimeout tidak boleh memakai closure render lama.
    const latest = useRef<Record<string, string>>({ ...values });
    const saved = useRef<Record<string, string>>({ ...values });
    const tableRef = useRef<HTMLTableElement>(null);

    useEffect(() => {
        const entries = Object.values(status);
        const errors = Object.entries(status).filter(
            ([, s]) => s === 'error' || s === 'invalid',
        );

        onSaveStateChange({
            saving:
                entries.filter((s) => s === 'saving').length +
                Object.keys(timers.current).length,
            failed: errors.length,
            lastError: errors.length > 0 ? 'Periksa sel bertanda merah.' : null,
        });
    }, [status, onSaveStateChange]);

    useEffect(() => {
        const pending = timers.current;

        return () => Object.values(pending).forEach(clearTimeout);
    }, []);

    const save = async (student: string, component: string) => {
        const cell = key(student, component);
        delete timers.current[cell];
        const raw = latest.current[cell] ?? '';

        if (raw === saved.current[cell]) {
            setStatus((current) => {
                const next = { ...current };
                delete next[cell];

                return next;
            });

            return;
        }

        const score = parseScore(raw);

        if (score === undefined) {
            setStatus((current) => ({ ...current, [cell]: 'invalid' }));

            return;
        }

        setStatus((current) => ({ ...current, [cell]: 'saving' }));

        try {
            const result = await sendJson<CellResult>(
                `/guru/akademik/nilai/komponen/${component}/sel`,
                'PUT',
                {
                    student,
                    score,
                },
            );
            saved.current[cell] = raw;
            setResults((current) => ({ ...current, [student]: result }));
            setStatus((current) => ({ ...current, [cell]: 'saved' }));
        } catch (error) {
            setStatus((current) => ({ ...current, [cell]: 'error' }));
            onSaveStateChange({
                saving: 0,
                failed: 1,
                lastError:
                    error instanceof HttpError
                        ? error.message
                        : 'Gagal menyimpan. Periksa koneksi.',
            });
        }
    };

    const change = (student: string, component: string, raw: string) => {
        const cell = key(student, component);
        latest.current[cell] = raw;
        setValues((current) => ({ ...current, [cell]: raw }));
        setStatus((current) => ({
            ...current,
            [cell]: parseScore(raw) === undefined ? 'invalid' : 'saving',
        }));

        clearTimeout(timers.current[cell]);
        timers.current[cell] = setTimeout(
            () => void save(student, component),
            DEBOUNCE_MS,
        );
    };

    const flush = (student: string, component: string) => {
        const cell = key(student, component);

        if (timers.current[cell]) {
            clearTimeout(timers.current[cell]);
            void save(student, component);
        }
    };

    const focusCell = (row: number, col: number) => {
        const input = tableRef.current?.querySelector<HTMLInputElement>(
            `[data-cell="${row}-${col}"]`,
        );
        input?.focus();
        input?.select();
    };

    const onKeyDown = (
        event: KeyboardEvent<HTMLInputElement>,
        row: number,
        col: number,
    ) => {
        const input = event.currentTarget;
        const atStart = input.selectionStart === 0 && input.selectionEnd === 0;
        const atEnd = input.selectionStart === input.value.length;

        if (event.key === 'Enter' || event.key === 'ArrowDown') {
            event.preventDefault();
            focusCell(row + 1, col);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            focusCell(row - 1, col);
        } else if (event.key === 'ArrowRight' && atEnd) {
            event.preventDefault();
            focusCell(row, col + 1);
        } else if (event.key === 'ArrowLeft' && atStart) {
            event.preventDefault();
            focusCell(row, col - 1);
        }
    };

    return (
        <div className="relative overflow-x-auto">
            <table
                ref={tableRef}
                className="w-full border-separate border-spacing-0 text-sm"
            >
                <thead>
                    <tr className="text-xs font-semibold text-slate-600">
                        <th className="sticky left-0 z-10 min-w-48 border-b border-slate-200 bg-slate-50 px-4 py-2 text-left">
                            Siswa
                        </th>
                        {components.map((component) => (
                            <th
                                key={component.public_id}
                                className="min-w-24 border-b border-slate-200 bg-slate-50 px-2 py-2 text-center"
                            >
                                {renderComponentHeader(component)}
                            </th>
                        ))}
                        <th className="border-b border-slate-200 bg-slate-50 px-3 py-2 text-center">
                            Akhir
                        </th>
                        <th className="border-b border-slate-200 bg-slate-50 px-3 py-2 text-center">
                            Pred.
                        </th>
                        <th className="min-w-56 border-b border-slate-200 bg-slate-50 px-3 py-2 text-left">
                            Deskripsi capaian
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {students.map((student, row) => {
                        const result = results[student.public_id];
                        const below =
                            result?.final !== null &&
                            result?.final !== undefined &&
                            result.final < kkm;

                        return (
                            <tr key={student.public_id} className="group">
                                <th
                                    scope="row"
                                    className="sticky left-0 z-10 border-b border-slate-100 bg-white px-4 py-1.5 text-left font-normal group-hover:bg-slate-50"
                                >
                                    <p className="truncate font-medium text-slate-900">
                                        {student.name}
                                    </p>
                                    <p className="text-xs text-slate-500 tabular-nums">
                                        {student.nis}
                                    </p>
                                </th>
                                {components.map((component, col) => {
                                    const cell = key(
                                        student.public_id,
                                        component.public_id,
                                    );
                                    const locked =
                                        readOnly ||
                                        component.published_at !== null;
                                    const cellStatus = status[cell];

                                    return (
                                        <td
                                            key={component.public_id}
                                            className="border-b border-slate-100 px-1.5 py-1 text-center"
                                        >
                                            {locked ? (
                                                <span className="inline-flex h-9 w-16 items-center justify-center gap-1 text-slate-700 tabular-nums">
                                                    {values[cell] || (
                                                        <span className="text-slate-300">
                                                            —
                                                        </span>
                                                    )}
                                                </span>
                                            ) : (
                                                <input
                                                    data-cell={`${row}-${col}`}
                                                    inputMode="decimal"
                                                    autoComplete="off"
                                                    aria-label={`Nilai ${component.name} ${student.name}`}
                                                    aria-invalid={
                                                        cellStatus ===
                                                            'invalid' ||
                                                        cellStatus === 'error'
                                                    }
                                                    value={values[cell] ?? ''}
                                                    onChange={(event) =>
                                                        change(
                                                            student.public_id,
                                                            component.public_id,
                                                            event.target.value,
                                                        )
                                                    }
                                                    onBlur={() =>
                                                        flush(
                                                            student.public_id,
                                                            component.public_id,
                                                        )
                                                    }
                                                    onFocus={(event) =>
                                                        event.currentTarget.select()
                                                    }
                                                    onKeyDown={(event) =>
                                                        onKeyDown(
                                                            event,
                                                            row,
                                                            col,
                                                        )
                                                    }
                                                    className={cn(
                                                        'h-9 w-16 rounded-md border text-center tabular-nums transition-colors duration-150 outline-none focus-visible:ring-2 focus-visible:ring-brand-500',
                                                        cellStatus ===
                                                            'invalid' ||
                                                            cellStatus ===
                                                                'error'
                                                            ? 'border-rose-400 bg-rose-50 text-rose-700'
                                                            : cellStatus ===
                                                                'saving'
                                                              ? 'border-amber-300 bg-amber-50/50'
                                                              : cellStatus ===
                                                                  'saved'
                                                                ? 'border-emerald-300'
                                                                : 'border-slate-200 hover:border-slate-300',
                                                    )}
                                                />
                                            )}
                                        </td>
                                    );
                                })}
                                <td
                                    className={cn(
                                        'border-b border-slate-100 px-3 py-1 text-center font-semibold tabular-nums',
                                        below
                                            ? 'text-rose-600'
                                            : 'text-slate-900',
                                    )}
                                    title={
                                        below
                                            ? `Di bawah KKM (${kkm})`
                                            : undefined
                                    }
                                >
                                    {result?.final ?? (
                                        <span className="font-normal text-slate-300">
                                            —
                                        </span>
                                    )}
                                </td>
                                <td className="border-b border-slate-100 px-3 py-1 text-center">
                                    {result?.predicate ? (
                                        <PredicateChip
                                            value={result.predicate}
                                        />
                                    ) : (
                                        <span className="text-slate-300">
                                            —
                                        </span>
                                    )}
                                </td>
                                <td className="border-b border-slate-100 px-3 py-1">
                                    <button
                                        type="button"
                                        disabled={
                                            readOnly &&
                                            !descriptions[student.public_id]
                                        }
                                        onClick={() =>
                                            onEditDescription(student, result)
                                        }
                                        className="flex w-full items-center gap-2 rounded-md px-2 py-1 text-left text-xs text-slate-600 hover:bg-slate-100 disabled:hover:bg-transparent"
                                    >
                                        <span className="line-clamp-2 flex-1">
                                            {descriptions[
                                                student.public_id
                                            ] ?? (
                                                <span className="text-slate-400 italic">
                                                    {readOnly
                                                        ? 'Belum ada'
                                                        : 'Tulis deskripsi…'}
                                                </span>
                                            )}
                                        </span>
                                        {!readOnly && (
                                            <PencilLine className="size-3.5 shrink-0 text-slate-400" />
                                        )}
                                    </button>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

const PREDICATE_CLASS: Record<PredicateValue, string> = {
    A: 'bg-emerald-100 text-emerald-700',
    B: 'bg-sky-100 text-sky-700',
    C: 'bg-amber-100 text-amber-700',
    D: 'bg-rose-100 text-rose-700',
};

export function PredicateChip({ value }: { value: PredicateValue }) {
    return (
        <span
            className={cn(
                'inline-flex size-7 items-center justify-center rounded-md text-xs font-bold',
                PREDICATE_CLASS[value],
            )}
        >
            {value}
        </span>
    );
}

export function LockedMark() {
    return (
        <Lock
            className="size-3 text-slate-400"
            aria-label="Terkunci (dipublikasikan)"
        />
    );
}
