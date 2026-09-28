import { useForm } from '@inertiajs/react';
import { Upload } from 'lucide-react';
import type { FormEvent } from 'react';
import { FileUploader } from '@/Components/shared/FileUploader';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Spinner } from '@/Components/ui/spinner';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    url: string;
};

export function ImportDialog({ open, onOpenChange, url }: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <ImportForm url={url} onClose={() => onOpenChange(false)} />
            </DialogContent>
        </Dialog>
    );
}

function ImportForm({ url, onClose }: { url: string; onClose: () => void }) {
    const form = useForm({ file: null as File | null });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <DialogHeader>
                <DialogTitle>Impor nilai dari Excel</DialogTitle>
                <DialogDescription>
                    Gunakan file hasil <strong>Ekspor Excel</strong>. Baris
                    dicocokkan lewat NIS, kolom lewat nama komponen. Sel kosong
                    dilewati; komponen yang sudah dipublikasikan tidak diubah.
                </DialogDescription>
            </DialogHeader>
            <FileUploader
                value={form.data.file ? [form.data.file] : []}
                onChange={(files) => form.setData('file', files[0] ?? null)}
                accept={[
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                    'text/csv',
                ]}
                maxSizeKb={5120}
                hint=".xlsx · maks 5 MB"
                error={form.errors.file}
            />
            <DialogFooter className="gap-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Batal
                </Button>
                <Button
                    type="submit"
                    disabled={!form.data.file || form.processing}
                >
                    {form.processing ? <Spinner /> : <Upload />}
                    Impor
                </Button>
            </DialogFooter>
        </form>
    );
}
