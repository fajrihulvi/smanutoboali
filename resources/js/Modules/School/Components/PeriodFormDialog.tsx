import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    /** URL tujuan & method. */
    action: { url: string; method: 'post' | 'put' };
    /** Tampilkan kolom nama (tahun ajaran); semester hanya tanggal. */
    withName: boolean;
    initial: { name?: string; start_date: string; end_date: string };
};

/**
 * Form dialog tahun ajaran (nama + tanggal) atau semester (tanggal saja).
 */
export function PeriodFormDialog({
    open,
    onOpenChange,
    title,
    description,
    action,
    withName,
    initial,
}: Props) {
    const form = useForm({
        name: initial.name ?? '',
        start_date: initial.start_date,
        end_date: initial.end_date,
    });

    useEffect(() => {
        if (open) {
            form.setDefaults({
                name: initial.name ?? '',
                start_date: initial.start_date,
                end_date: initial.end_date,
            });
            form.reset();
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps -- reset hanya saat dialog dibuka
    }, [open]);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.submit(action.method, action.url, {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        {description && (
                            <DialogDescription>{description}</DialogDescription>
                        )}
                    </DialogHeader>

                    {withName && (
                        <div className="grid gap-2">
                            <Label htmlFor="period-name">
                                Nama tahun ajaran
                            </Label>
                            <Input
                                id="period-name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                                placeholder="2026/2027"
                                aria-invalid={!!form.errors.name}
                            />
                            <InputError message={form.errors.name} />
                        </div>
                    )}

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="period-start">Tanggal mulai</Label>
                            <Input
                                id="period-start"
                                type="date"
                                value={form.data.start_date}
                                onChange={(event) =>
                                    form.setData(
                                        'start_date',
                                        event.target.value,
                                    )
                                }
                                aria-invalid={!!form.errors.start_date}
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="period-end">Tanggal selesai</Label>
                            <Input
                                id="period-end"
                                type="date"
                                value={form.data.end_date}
                                onChange={(event) =>
                                    form.setData('end_date', event.target.value)
                                }
                                aria-invalid={!!form.errors.end_date}
                            />
                        </div>
                    </div>
                    <InputError
                        message={form.errors.start_date ?? form.errors.end_date}
                    />

                    <DialogFooter className="gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Batal
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {form.processing && <Spinner />}
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
