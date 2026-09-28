import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import { requestJson } from '@/lib/http';
import { cn } from '@/lib/utils';

export type PickedStudent = {
    public_id: string;
    name: string;
    classroom?: string | null;
};

type Candidate = PickedStudent & { nis: string };

type Props = {
    value: PickedStudent[];
    onChange: (students: PickedStudent[]) => void;
    /** Endpoint JSON pencarian siswa (?q=). */
    searchUrl: string;
    invalid?: boolean;
};

/**
 * Pilih satu/beberapa siswa: cari (nama/NIS), klik untuk menambah, chip untuk menghapus.
 */
export function StudentPicker({ value, onChange, searchUrl, invalid }: Props) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Candidate[] | null>(null);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const load = (term: string) =>
        requestJson<{ data: Candidate[] }>(
            `${searchUrl}?${new URLSearchParams({ q: term })}`,
        )
            .then((response) => setResults(response.data))
            .catch(() => setResults([]));

    useEffect(() => {
        void load('');
        // eslint-disable-next-line react-hooks/exhaustive-deps -- muat awal sekali
    }, []);

    const selected = new Set(value.map((student) => student.public_id));

    return (
        <div
            className={cn(
                'space-y-3 rounded-xl border p-3',
                invalid ? 'border-rose-400' : 'border-slate-200',
            )}
        >
            {value.length > 0 && (
                <ul
                    className="flex flex-wrap gap-1.5"
                    aria-label="Siswa terpilih"
                >
                    {value.map((student) => (
                        <li
                            key={student.public_id}
                            className="flex items-center gap-1 rounded-full bg-brand-50 py-1 pr-1 pl-3 text-sm text-brand-800"
                        >
                            {student.name}
                            <button
                                type="button"
                                onClick={() =>
                                    onChange(
                                        value.filter(
                                            (s) =>
                                                s.public_id !==
                                                student.public_id,
                                        ),
                                    )
                                }
                                className="flex size-5 items-center justify-center rounded-full hover:bg-brand-100"
                                aria-label={`Hapus ${student.name}`}
                            >
                                <X className="size-3" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                <Input
                    value={query}
                    onChange={(event) => {
                        const term = event.target.value;
                        setQuery(term);

                        if (timer.current) {
                            clearTimeout(timer.current);
                        }

                        timer.current = setTimeout(() => void load(term), 300);
                    }}
                    placeholder="Cari nama atau NIS…"
                    className="pl-9"
                    aria-label="Cari siswa"
                />
            </div>
            <ul
                className="max-h-56 space-y-0.5 overflow-y-auto"
                aria-busy={results === null}
            >
                {results === null && <Spinner className="mx-auto my-4" />}
                {results?.length === 0 && (
                    <li className="py-4 text-center text-sm text-slate-500">
                        Tidak ada siswa yang cocok.
                    </li>
                )}
                {results?.map((student) => {
                    const isPicked = selected.has(student.public_id);

                    return (
                        <li key={student.public_id}>
                            <button
                                type="button"
                                disabled={isPicked}
                                onClick={() =>
                                    onChange([
                                        ...value,
                                        {
                                            public_id: student.public_id,
                                            name: student.name,
                                            classroom: student.classroom,
                                        },
                                    ])
                                }
                                className="flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:opacity-50"
                            >
                                <span className="min-w-0">
                                    <span className="block truncate font-medium text-slate-900">
                                        {student.name}
                                    </span>
                                    <span className="block text-xs text-slate-500 tabular-nums">
                                        {student.nis}
                                        {student.classroom &&
                                            ` · ${student.classroom}`}
                                    </span>
                                </span>
                                <span className="shrink-0 text-xs text-brand-700">
                                    {isPicked ? 'Dipilih' : 'Tambah'}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
