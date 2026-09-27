import { useForm } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
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
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type {
    DayOption,
    Option,
    ScheduleEntry,
} from '@/Modules/Academic/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = jadwal baru pada `defaultDay`. */
    schedule: ScheduleEntry | null;
    defaultDay: number;
    classroomName: string;
    assignments: Option[];
    days: DayOption[];
    onDelete: (schedule: ScheduleEntry) => void;
};

const URL = '/operator/akademik/jadwal';

export function ScheduleFormDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <ScheduleForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function ScheduleForm({
    schedule,
    defaultDay,
    classroomName,
    assignments,
    days,
    onDelete,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const form = useForm({
        teaching_assignment:
            schedule?.assignment ?? assignments[0]?.value ?? '',
        day_of_week: String(schedule?.day ?? defaultDay),
        start_time: schedule?.start_time ?? '07:30',
        end_time: schedule?.end_time ?? '08:10',
        room: schedule?.room ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: onClose };

        if (schedule) {
            form.put(`${URL}/${schedule.public_id}`, options);
        } else {
            form.post(URL, options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>
                    {schedule ? 'Ubah jadwal' : 'Tambah jadwal'}
                </DialogTitle>
                <DialogDescription>Rombel {classroomName}</DialogDescription>
            </DialogHeader>

            <div className="grid gap-2">
                <Label htmlFor="schedule-assignment">
                    Mata pelajaran & guru
                </Label>
                <NativeSelect
                    id="schedule-assignment"
                    value={form.data.teaching_assignment}
                    onChange={(event) =>
                        form.setData('teaching_assignment', event.target.value)
                    }
                    aria-invalid={!!form.errors.teaching_assignment}
                >
                    {assignments.map((assignment) => (
                        <option key={assignment.value} value={assignment.value}>
                            {assignment.label}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.teaching_assignment} />
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="schedule-day">Hari</Label>
                    <NativeSelect
                        id="schedule-day"
                        value={form.data.day_of_week}
                        onChange={(event) =>
                            form.setData('day_of_week', event.target.value)
                        }
                        aria-invalid={!!form.errors.day_of_week}
                    >
                        {days.map((day) => (
                            <option key={day.value} value={day.value}>
                                {day.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.day_of_week} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="schedule-start">Mulai</Label>
                    <Input
                        id="schedule-start"
                        type="time"
                        value={form.data.start_time}
                        onChange={(event) =>
                            form.setData('start_time', event.target.value)
                        }
                        aria-invalid={!!form.errors.start_time}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="schedule-end">Selesai</Label>
                    <Input
                        id="schedule-end"
                        type="time"
                        value={form.data.end_time}
                        onChange={(event) =>
                            form.setData('end_time', event.target.value)
                        }
                        aria-invalid={!!form.errors.end_time}
                    />
                </div>
            </div>
            <InputError
                message={form.errors.start_time ?? form.errors.end_time}
            />

            <div className="grid gap-2">
                <Label htmlFor="schedule-room">Ruang (opsional)</Label>
                <Input
                    id="schedule-room"
                    value={form.data.room}
                    onChange={(event) =>
                        form.setData('room', event.target.value)
                    }
                    placeholder="Lab Komputer"
                    aria-invalid={!!form.errors.room}
                />
                <InputError message={form.errors.room} />
            </div>

            <DialogFooter className="gap-2 sm:items-center">
                {schedule && (
                    <Button
                        type="button"
                        variant="ghost"
                        className="text-rose-600 hover:bg-rose-50 hover:text-rose-700 sm:mr-auto"
                        onClick={() => {
                            onClose();
                            onDelete(schedule);
                        }}
                    >
                        <Trash2 />
                        Hapus
                    </Button>
                )}
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
