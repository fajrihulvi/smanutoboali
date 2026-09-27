import { useForm } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import { requestJson } from '@/lib/http';
import { cn } from '@/lib/utils';

type Candidate = {
    public_id: string;
    nis: string;
    name: string;
    gender: string;
    current_classroom: string | null;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    classroomPublicId: string;
    classroomName: string;
};

export function EnrollStudentsDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <EnrollForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function EnrollForm({
    classroomPublicId,
    classroomName,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const [query, setQuery] = useState('');
    const [onlyUnassigned, setOnlyUnassigned] = useState(true);
    const [results, setResults] = useState<Candidate[] | null>(null);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const form = useForm({ students: [] as string[], move: false });

    const load = (term: string, unassigned: boolean) => {
        const params = new URLSearchParams({
            q: term,
            tanpa_rombel: unassigned ? '1' : '0',
        });

        requestJson<{ data: Candidate[] }>(
            `/operator/master-data/kelas/${classroomPublicId}/calon-siswa?${params}`,
        )
            .then((response) => setResults(response.data))
            .catch(() => setResults([]));
    };

    useEffect(() => {
        load('', true);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- muat awal sekali saat dialog dibuka
    }, []);

    const onQueryChange = (value: string) => {
        setQuery(value);

        if (timer.current) {
            clearTimeout(timer.current);
        }

        timer.current = setTimeout(() => load(value, onlyUnassigned), 300);
    };

    const toggle = (publicId: string, checked: boolean) => {
        form.setData(
            'students',
            checked
                ? [...form.data.students, publicId]
                : form.data.students.filter((id) => id !== publicId),
        );
    };

    const selectedElsewhere = (results ?? []).some(
        (candidate) =>
            form.data.students.includes(candidate.public_id) &&
            candidate.current_classroom,
    );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/operator/master-data/kelas/${classroomPublicId}/siswa`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>Tambah siswa ke {classroomName}</DialogTitle>
                <DialogDescription>
                    Pilih siswa aktif. Maksimal 100 siswa per kali simpan.
                </DialogDescription>
            </DialogHeader>

            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div className="relative flex-1">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        value={query}
                        onChange={(event) => onQueryChange(event.target.value)}
                        placeholder="Cari nama atau NIS…"
                        className="pl-9"
                        aria-label="Cari siswa"
                    />
                </div>
                <label className="flex items-center gap-2 text-sm whitespace-nowrap text-slate-600">
                    <Checkbox
                        checked={onlyUnassigned}
                        onCheckedChange={(checked) => {
                            setOnlyUnassigned(checked === true);
                            load(query, checked === true);
                        }}
                    />
                    Belum punya rombel
                </label>
            </div>

            <div
                className="max-h-72 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-1"
                aria-busy={results === null}
            >
                {results === null && <Spinner className="mx-auto my-6" />}
                {results?.length === 0 && (
                    <p className="py-6 text-center text-sm text-slate-500">
                        Tidak ada siswa yang cocok.
                    </p>
                )}
                {results?.map((candidate) => {
                    const checked = form.data.students.includes(
                        candidate.public_id,
                    );

                    return (
                        <label
                            key={candidate.public_id}
                            className={cn(
                                'flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition-colors duration-150',
                                checked ? 'bg-brand-50' : 'hover:bg-slate-50',
                            )}
                        >
                            <Checkbox
                                checked={checked}
                                onCheckedChange={(state) =>
                                    toggle(candidate.public_id, state === true)
                                }
                            />
                            <span className="min-w-0 flex-1">
                                <span className="block truncate text-sm font-medium text-slate-900">
                                    {candidate.name}
                                </span>
                                <span className="block text-xs text-slate-500 tabular-nums">
                                    NIS {candidate.nis} · {candidate.gender}
                                    {candidate.current_classroom &&
                                        ` · sekarang di ${candidate.current_classroom}`}
                                </span>
                            </span>
                        </label>
                    );
                })}
            </div>

            {selectedElsewhere && (
                <label className="flex items-start gap-2 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                    <Checkbox
                        className="mt-0.5"
                        checked={form.data.move}
                        onCheckedChange={(checked) =>
                            form.setData('move', checked === true)
                        }
                    />
                    Pindahkan siswa yang sudah punya rombel tahun ini (riwayat
                    rombel lama tetap tersimpan).
                </label>
            )}

            <InputError
                message={form.errors.students ?? form.errors['students.0']}
            />

            <DialogFooter className="gap-2 sm:items-center">
                <p className="text-sm text-slate-500 sm:mr-auto">
                    {form.data.students.length} dipilih
                </p>
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button
                    type="submit"
                    disabled={
                        form.processing || form.data.students.length === 0
                    }
                >
                    {form.processing && <Spinner />}
                    Tambahkan
                </Button>
            </DialogFooter>
        </form>
    );
}
