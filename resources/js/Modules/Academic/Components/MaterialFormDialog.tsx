import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { FileUploader } from '@/Components/shared/FileUploader';
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
import { Textarea } from '@/Components/ui/textarea';
import { cn } from '@/lib/utils';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { MaterialRow, Option } from '@/Modules/Academic/types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    material: MaterialRow | null;
    assignments: Option[];
    types: Option[];
    defaultAssignment?: string;
    maxUploadKb: number;
};

const URL = '/guru/akademik/materi';

const ACCEPT = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/msword',
    'application/vnd.ms-powerpoint',
    'application/vnd.ms-excel',
    'image/jpeg',
    'image/png',
    'image/webp',
];

export function MaterialFormDialog({ open, onOpenChange, ...props }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <MaterialForm {...props} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function MaterialForm({
    material,
    assignments,
    types,
    defaultAssignment,
    maxUploadKb,
    onClose,
}: Omit<Props, 'open' | 'onOpenChange'> & { onClose: () => void }) {
    const form = useForm({
        teaching_assignment:
            material?.assignment ??
            defaultAssignment ??
            assignments[0]?.value ??
            '',
        title: material?.title ?? '',
        description: material?.description ?? '',
        type: material?.type.value ?? 'FILE',
        url: material?.url ?? '',
        file: null as File | null,
        ...(material ? { _method: 'put' } : {}),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(material ? `${URL}/${material.public_id}` : URL, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <DialogHeader>
                <DialogTitle>
                    {material ? 'Ubah materi' : 'Tambah materi'}
                </DialogTitle>
            </DialogHeader>

            <div className="grid gap-2">
                <Label htmlFor="material-assignment">
                    Mata pelajaran & rombel
                </Label>
                <NativeSelect
                    id="material-assignment"
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

            <div className="grid gap-2">
                <Label htmlFor="material-title">Judul</Label>
                <Input
                    id="material-title"
                    value={form.data.title}
                    onChange={(event) =>
                        form.setData('title', event.target.value)
                    }
                    aria-invalid={!!form.errors.title}
                />
                <InputError message={form.errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="material-description">
                    Deskripsi (opsional)
                </Label>
                <Textarea
                    id="material-description"
                    rows={3}
                    value={form.data.description}
                    onChange={(event) =>
                        form.setData('description', event.target.value)
                    }
                    aria-invalid={!!form.errors.description}
                />
                <InputError message={form.errors.description} />
            </div>

            <fieldset className="grid gap-2">
                <legend className="mb-2 text-sm font-medium text-slate-900">
                    Jenis materi
                </legend>
                <div className="grid grid-cols-2 gap-2" role="radiogroup">
                    {types.map((type) => (
                        <label
                            key={type.value}
                            className={cn(
                                'flex cursor-pointer items-center justify-center rounded-xl border px-3 py-2 text-sm font-medium transition-colors duration-150',
                                form.data.type === type.value
                                    ? 'border-brand-600 bg-brand-50 text-brand-700'
                                    : 'border-slate-200 text-slate-600 hover:border-brand-300',
                            )}
                        >
                            <input
                                type="radio"
                                name="material-type"
                                value={type.value}
                                checked={form.data.type === type.value}
                                onChange={() =>
                                    form.setData('type', type.value)
                                }
                                className="sr-only"
                            />
                            {type.label}
                        </label>
                    ))}
                </div>
            </fieldset>

            {form.data.type === 'LINK' ? (
                <div className="grid gap-2">
                    <Label htmlFor="material-url">Tautan</Label>
                    <Input
                        id="material-url"
                        type="url"
                        inputMode="url"
                        placeholder="https://"
                        value={form.data.url}
                        onChange={(event) =>
                            form.setData('url', event.target.value)
                        }
                        aria-invalid={!!form.errors.url}
                    />
                    <InputError message={form.errors.url} />
                </div>
            ) : (
                <div className="grid gap-2">
                    {material?.file && !form.data.file && (
                        <p className="text-sm text-slate-600">
                            File saat ini:{' '}
                            <span className="font-medium">
                                {material.file.name}
                            </span>
                            . Unggah file baru untuk menggantinya.
                        </p>
                    )}
                    <FileUploader
                        value={form.data.file ? [form.data.file] : []}
                        onChange={(files) =>
                            form.setData('file', files[0] ?? null)
                        }
                        accept={ACCEPT}
                        maxSizeKb={maxUploadKb}
                        hint={`PDF, Word, PowerPoint, Excel, atau gambar · maks ${Math.round(maxUploadKb / 1024)} MB`}
                        error={form.errors.file}
                    />
                </div>
            )}

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
