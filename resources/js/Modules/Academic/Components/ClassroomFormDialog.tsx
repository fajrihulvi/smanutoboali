import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type {
    ClassroomRow,
    GradeOption,
    Option,
} from '@/Modules/Academic/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** null = tambah rombel baru di tahun ajaran `yearPublicId`. */
    classroom: ClassroomRow | null;
    yearPublicId?: string;
    grades: GradeOption[];
    teachers: Option[];
};

export function ClassroomFormDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <ClassroomForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function ClassroomForm({
    classroom,
    yearPublicId,
    grades,
    teachers,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const form = useForm({
        academic_year: yearPublicId ?? '',
        name: classroom?.name ?? '',
        grade: String(classroom?.grade ?? grades[0]?.value ?? ''),
        homeroom_teacher: classroom?.homeroom_teacher?.public_id ?? '',
        capacity: classroom?.capacity ? String(classroom.capacity) : '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = { preserveScroll: true, onSuccess: onClose };

        if (classroom) {
            form.put(
                `/operator/master-data/kelas/${classroom.public_id}`,
                options,
            );
        } else {
            form.post('/operator/master-data/kelas', options);
        }
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>
                    {classroom
                        ? `Ubah rombel ${classroom.name}`
                        : 'Tambah rombel'}
                </DialogTitle>
            </DialogHeader>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="classroom-name">Nama rombel</Label>
                    <Input
                        id="classroom-name"
                        value={form.data.name}
                        onChange={(event) =>
                            form.setData('name', event.target.value)
                        }
                        placeholder="VII-A"
                        aria-invalid={!!form.errors.name}
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="classroom-grade">Tingkat</Label>
                    <NativeSelect
                        id="classroom-grade"
                        value={form.data.grade}
                        onChange={(event) =>
                            form.setData('grade', event.target.value)
                        }
                        aria-invalid={!!form.errors.grade}
                    >
                        {grades.map((grade) => (
                            <option key={grade.value} value={grade.value}>
                                {grade.label}
                            </option>
                        ))}
                    </NativeSelect>
                    <InputError message={form.errors.grade} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="classroom-homeroom">Wali kelas</Label>
                <NativeSelect
                    id="classroom-homeroom"
                    value={form.data.homeroom_teacher}
                    onChange={(event) =>
                        form.setData('homeroom_teacher', event.target.value)
                    }
                    aria-invalid={!!form.errors.homeroom_teacher}
                >
                    <option value="">— Belum ditentukan —</option>
                    {teachers.map((teacher) => (
                        <option key={teacher.value} value={teacher.value}>
                            {teacher.label}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.homeroom_teacher} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="classroom-capacity">Kapasitas (opsional)</Label>
                <Input
                    id="classroom-capacity"
                    type="number"
                    min={1}
                    max={60}
                    value={form.data.capacity}
                    onChange={(event) =>
                        form.setData('capacity', event.target.value)
                    }
                    className="sm:max-w-32"
                    aria-invalid={!!form.errors.capacity}
                />
                <InputError message={form.errors.capacity} />
            </div>
            <InputError message={form.errors.academic_year} />

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
