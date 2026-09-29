import { ExternalLink } from 'lucide-react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { formatDate } from '@/lib/format';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { FormCard } from '@/Modules/Cms/Components/FormCard';
import { TRANSITIONS } from '@/Modules/Cms/types';
import type { ContentStatus, ContentStatusValue } from '@/Modules/Cms/types';

type Props = {
    statuses: Option[];
    /** Status tersimpan (null saat membuat). */
    current: ContentStatus | null;
    status: string;
    publishAt: string;
    onStatusChange: (value: string) => void;
    onPublishAtChange: (value: string) => void;
    errors: { status?: string; publish_at?: string };
    processing: boolean;
    previewUrl?: string;
    updatedAt?: string;
};

const HINTS: Record<ContentStatusValue, string> = {
    DRAFT: 'Belum tampil di website.',
    REVIEW: 'Tandai siap diperiksa. Belum tampil di website.',
    PUBLISHED:
        'Tampil di website. Isi jadwal bila ingin tayang otomatis nanti.',
    ARCHIVED: 'Disembunyikan dari website, tetap tersimpan.',
};

/** ISO → nilai input datetime-local. */
export function toLocalInput(iso: string | null): string {
    return iso ? formatDate(iso, "yyyy-MM-dd'T'HH:mm") : '';
}

/**
 * Status & jadwal terbit + tombol simpan + tautan pratinjau.
 */
export function PublishCard({
    statuses,
    current,
    status,
    publishAt,
    onStatusChange,
    onPublishAtChange,
    errors,
    processing,
    previewUrl,
    updatedAt,
}: Props) {
    const allowed = current
        ? [current.value, ...TRANSITIONS[current.value]]
        : (statuses.map((s) => s.value) as ContentStatusValue[]);
    const options = statuses.filter((s) =>
        allowed.includes(s.value as ContentStatusValue),
    );

    return (
        <FormCard title="Penerbitan">
            <div className="grid gap-2">
                <Label htmlFor="content-status">Status</Label>
                <NativeSelect
                    id="content-status"
                    value={status}
                    onChange={(e) => onStatusChange(e.target.value)}
                    aria-invalid={!!errors.status}
                >
                    {options.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </NativeSelect>
                <p className="text-xs text-slate-500">
                    {HINTS[status as ContentStatusValue]}
                </p>
                <InputError message={errors.status} />
            </div>
            {status === 'PUBLISHED' && (
                <div className="grid gap-2">
                    <Label htmlFor="content-publish-at">
                        Jadwal terbit (opsional)
                    </Label>
                    <Input
                        id="content-publish-at"
                        type="datetime-local"
                        value={publishAt}
                        onChange={(e) => onPublishAtChange(e.target.value)}
                        aria-invalid={!!errors.publish_at}
                    />
                    <p className="text-xs text-slate-500">
                        Kosongkan agar langsung tayang.
                    </p>
                    <InputError message={errors.publish_at} />
                </div>
            )}
            <div className="flex flex-col gap-2">
                <Button type="submit" disabled={processing}>
                    {processing && <Spinner />}
                    Simpan
                </Button>
                {previewUrl && (
                    <Button variant="outline" asChild>
                        <a href={previewUrl} target="_blank" rel="noopener">
                            <ExternalLink />
                            Pratinjau
                        </a>
                    </Button>
                )}
            </div>
            {updatedAt && (
                <p className="text-xs text-slate-400">
                    Terakhir diubah {formatDate(updatedAt, 'd MMM yyyy, HH.mm')}
                </p>
            )}
        </FormCard>
    );
}
