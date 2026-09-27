import { useForm } from '@inertiajs/react';
import { Search, UserRoundPlus, Users } from 'lucide-react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Spinner } from '@/Components/ui/spinner';
import { requestJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import { FormField } from '@/Modules/People/Components/FormField';
import type { Option } from '@/Modules/People/types';

type SearchResult = {
    public_id: string;
    name: string;
    phone: string | null;
    email: string | null;
    students_count: number;
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    studentPublicId: string;
    relationships: Option[];
    /** Public id orang tua yang sudah terhubung (disembunyikan dari hasil). */
    linked: string[];
};

/**
 * Isi dialog dipasang ulang setiap kali dibuka (Radix melepas DialogContent saat
 * ditutup), sehingga state form & pencarian selalu mulai bersih.
 */
export function AttachGuardianDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <AttachGuardianForm
                    onClose={() => onOpenChange(false)}
                    {...props}
                />
            </DialogContent>
        </Dialog>
    );
}

function AttachGuardianForm({
    onClose,
    studentPublicId,
    relationships,
    linked,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[] | null>(null);
    const [searching, setSearching] = useState(true);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    const form = useForm({
        mode: 'existing' as 'existing' | 'new',
        guardian: '',
        new_name: '',
        new_phone: '',
        new_email: '',
        relationship: 'IBU',
        is_primary: false,
    });

    const search = (term: string) => {
        setSearching(true);
        requestJson<{ data: SearchResult[] }>(
            `/operator/master-data/orang-tua-cari?q=${encodeURIComponent(term)}`,
        )
            .then((response) => setResults(response.data))
            .catch(() => setResults([]))
            .finally(() => setSearching(false));
    };

    useEffect(() => {
        requestJson<{ data: SearchResult[] }>(
            '/operator/master-data/orang-tua-cari?q=',
        )
            .then((response) => setResults(response.data))
            .catch(() => setResults([]))
            .finally(() => setSearching(false));
    }, []);

    const onQueryChange = (value: string) => {
        setQuery(value);

        if (timer.current) {
            clearTimeout(timer.current);
        }

        timer.current = setTimeout(() => search(value), 300);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/operator/master-data/siswa/${studentPublicId}/orang-tua`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const visible = (results ?? []).filter(
        (result) => !linked.includes(result.public_id),
    );

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>Hubungkan orang tua</DialogTitle>
                <DialogDescription>
                    Pilih data orang tua yang sudah ada (mis. orang tua
                    kakak/adik) atau buat baru.
                </DialogDescription>
            </DialogHeader>

            <div className="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
                {(['existing', 'new'] as const).map((mode) => (
                    <button
                        key={mode}
                        type="button"
                        onClick={() => form.setData('mode', mode)}
                        className={cn(
                            'flex items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-colors duration-150',
                            form.data.mode === mode
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-slate-600',
                        )}
                    >
                        {mode === 'existing' ? (
                            <Users className="size-4" />
                        ) : (
                            <UserRoundPlus className="size-4" />
                        )}
                        {mode === 'existing' ? 'Pilih yang ada' : 'Buat baru'}
                    </button>
                ))}
            </div>

            {form.data.mode === 'existing' ? (
                <div className="space-y-3">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            value={query}
                            onChange={(event) =>
                                onQueryChange(event.target.value)
                            }
                            placeholder="Cari nama, telepon, email…"
                            className="pl-9"
                            aria-label="Cari orang tua"
                        />
                    </div>
                    <div
                        className="max-h-56 space-y-1 overflow-y-auto"
                        role="listbox"
                        aria-busy={searching}
                    >
                        {searching && results === null && (
                            <Spinner className="mx-auto my-4" />
                        )}
                        {results !== null && visible.length === 0 && (
                            <p className="py-6 text-center text-sm text-slate-500">
                                Tidak ada hasil. Gunakan “Buat baru”.
                            </p>
                        )}
                        {visible.map((result) => (
                            <button
                                key={result.public_id}
                                type="button"
                                role="option"
                                aria-selected={
                                    form.data.guardian === result.public_id
                                }
                                onClick={() =>
                                    form.setData('guardian', result.public_id)
                                }
                                className={cn(
                                    'w-full rounded-xl border px-3 py-2 text-left transition-colors duration-150',
                                    form.data.guardian === result.public_id
                                        ? 'border-brand-300 bg-brand-50'
                                        : 'border-slate-200 hover:bg-slate-50',
                                )}
                            >
                                <p className="text-sm font-medium text-slate-900">
                                    {result.name}
                                </p>
                                <p className="text-xs text-slate-500">
                                    {[result.phone, result.email]
                                        .filter(Boolean)
                                        .join(' · ') || 'Tanpa kontak'}
                                    {result.students_count > 0 &&
                                        ` · ${result.students_count} anak terhubung`}
                                </p>
                            </button>
                        ))}
                    </div>
                    <InputError message={form.errors.guardian} />
                </div>
            ) : (
                <div className="space-y-4">
                    <FormField
                        id="new_name"
                        label="Nama orang tua"
                        error={form.errors.new_name}
                    >
                        <Input
                            id="new_name"
                            value={form.data.new_name}
                            onChange={(event) =>
                                form.setData('new_name', event.target.value)
                            }
                            aria-invalid={!!form.errors.new_name}
                        />
                    </FormField>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            id="new_phone"
                            label="Telepon / WhatsApp"
                            error={form.errors.new_phone}
                        >
                            <Input
                                id="new_phone"
                                inputMode="tel"
                                value={form.data.new_phone}
                                onChange={(event) =>
                                    form.setData(
                                        'new_phone',
                                        event.target.value,
                                    )
                                }
                            />
                        </FormField>
                        <FormField
                            id="new_email"
                            label="Email"
                            error={form.errors.new_email}
                        >
                            <Input
                                id="new_email"
                                type="email"
                                value={form.data.new_email}
                                onChange={(event) =>
                                    form.setData(
                                        'new_email',
                                        event.target.value,
                                    )
                                }
                            />
                        </FormField>
                    </div>
                </div>
            )}

            <div className="grid gap-4 sm:grid-cols-2 sm:items-end">
                <FormField
                    id="relationship"
                    label="Hubungan"
                    error={form.errors.relationship}
                >
                    <Select
                        value={form.data.relationship}
                        onValueChange={(value) =>
                            form.setData('relationship', value)
                        }
                    >
                        <SelectTrigger id="relationship">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {relationships.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </FormField>
                <label className="flex items-center gap-3 pb-2 text-sm text-slate-700">
                    <Checkbox
                        checked={form.data.is_primary}
                        onCheckedChange={(checked) =>
                            form.setData('is_primary', checked === true)
                        }
                    />
                    Jadikan orang tua utama
                </label>
            </div>

            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Hubungkan
                </Button>
            </DialogFooter>
        </form>
    );
}
