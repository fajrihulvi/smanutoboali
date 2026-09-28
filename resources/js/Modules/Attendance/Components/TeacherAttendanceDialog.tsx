import { useForm } from '@inertiajs/react';
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
import { formatDate } from '@/lib/format';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { ON_SITE } from '@/Modules/Attendance/lib/teacher';
import type {
    TeacherRecord,
    TeacherStatusValue,
} from '@/Modules/Attendance/lib/teacher';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    teacher: { public_id: string; name: string } | null;
    record: TeacherRecord | null;
    date: string;
    statuses: Option[];
};

export function TeacherAttendanceDialog({
    open,
    onOpenChange,
    ...props
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                {props.teacher && (
                    <CorrectionForm
                        {...props}
                        teacher={props.teacher}
                        onClose={() => onOpenChange(false)}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function CorrectionForm({
    teacher,
    record,
    date,
    statuses,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange' | 'teacher'> & {
    teacher: { public_id: string; name: string };
    onClose: () => void;
}) {
    const form = useForm({
        date,
        status: (record?.status.value ?? 'HADIR') as TeacherStatusValue,
        check_in: record?.check_in ?? '07:00',
        check_out: record?.check_out ?? '',
        note: record?.note ?? '',
    });
    const onSite = ON_SITE.includes(form.data.status);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            ...data,
            check_in: onSite ? data.check_in : null,
            check_out: onSite && data.check_out ? data.check_out : null,
        }));
        form.put(`/operator/presensi-guru/${teacher.public_id}`, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>
                    {record ? 'Koreksi presensi' : 'Input presensi'}
                </DialogTitle>
                <DialogDescription>
                    {teacher.name} · {formatDate(date, 'EEEE, d MMMM yyyy')}
                </DialogDescription>
            </DialogHeader>

            <div className="grid gap-2">
                <Label htmlFor="teacher-status">Status</Label>
                <NativeSelect
                    id="teacher-status"
                    value={form.data.status}
                    onChange={(event) =>
                        form.setData(
                            'status',
                            event.target.value as TeacherStatusValue,
                        )
                    }
                >
                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.status ?? form.errors.date} />
            </div>

            {onSite && (
                <div className="grid grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="teacher-check-in">Jam masuk</Label>
                        <Input
                            id="teacher-check-in"
                            type="time"
                            value={form.data.check_in}
                            onChange={(event) =>
                                form.setData('check_in', event.target.value)
                            }
                            aria-invalid={!!form.errors.check_in}
                        />
                        <InputError message={form.errors.check_in} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="teacher-check-out">Jam pulang</Label>
                        <Input
                            id="teacher-check-out"
                            type="time"
                            value={form.data.check_out}
                            onChange={(event) =>
                                form.setData('check_out', event.target.value)
                            }
                            aria-invalid={!!form.errors.check_out}
                        />
                        <InputError message={form.errors.check_out} />
                    </div>
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="teacher-note">Catatan</Label>
                <Input
                    id="teacher-note"
                    value={form.data.note}
                    maxLength={255}
                    placeholder="Mis. surat izin, tugas ke dinas pendidikan"
                    onChange={(event) =>
                        form.setData('note', event.target.value)
                    }
                    aria-invalid={!!form.errors.note}
                />
                <InputError message={form.errors.note} />
            </div>

            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button type="submit" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    Simpan
                </Button>
            </DialogFooter>
        </form>
    );
}
