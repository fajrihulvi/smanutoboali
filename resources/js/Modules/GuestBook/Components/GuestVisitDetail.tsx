import { Link } from '@inertiajs/react';
import { MessageCircle, Pencil } from 'lucide-react';
import { useState } from 'react';
import { Lightbox } from '@/Components/shared/Lightbox';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { formatDateTime } from '@/lib/format';
import type { GuestVisitRow } from '@/Modules/GuestBook/types';

type Props = {
    visit: GuestVisitRow | null;
    onClose: () => void;
    editBase?: string;
};

/**
 * Detail satu kunjungan tamu: foto (bisa diperbesar), identitas, dan keperluan.
 */
export function GuestVisitDetail({ visit, onClose, editBase }: Props) {
    const [zoom, setZoom] = useState<number | null>(null);

    return (
        <>
            <Dialog
                open={visit !== null}
                onOpenChange={(open) => !open && onClose()}
            >
                <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
                    {visit && (
                        <>
                            <DialogHeader>
                                <DialogTitle>Detail tamu</DialogTitle>
                                <DialogDescription>
                                    {formatDateTime(visit.visited_at)} ·{' '}
                                    {visit.source.label}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="flex items-center gap-4">
                                {visit.photo_url ? (
                                    <button
                                        type="button"
                                        onClick={() => setZoom(0)}
                                        className="shrink-0 rounded-full focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:outline-none"
                                        aria-label="Perbesar foto tamu"
                                    >
                                        <PersonAvatar
                                            name={visit.full_name}
                                            photoUrl={visit.photo_thumb_url}
                                            className="size-20"
                                        />
                                    </button>
                                ) : (
                                    <PersonAvatar
                                        name={visit.full_name}
                                        className="size-20"
                                    />
                                )}
                                <div className="min-w-0">
                                    <p className="text-lg font-semibold break-words text-slate-900">
                                        {visit.full_name}
                                    </p>
                                    <p className="text-sm break-words text-slate-500">
                                        {visit.institution}
                                    </p>
                                </div>
                            </div>
                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-slate-500">
                                        Kategori tamu
                                    </dt>
                                    <dd className="font-medium text-slate-900">
                                        {visit.category.label}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-slate-500">
                                        Bertemu dengan
                                    </dt>
                                    <dd className="font-medium text-slate-900">
                                        {visit.host.label}
                                    </dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-slate-500">
                                        Nomor WhatsApp
                                    </dt>
                                    <dd className="font-medium text-slate-900 tabular-nums">
                                        {visit.whatsapp}
                                    </dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-slate-500">
                                        Keperluan
                                    </dt>
                                    <dd className="whitespace-pre-line text-slate-900">
                                        {visit.purpose}
                                    </dd>
                                </div>
                                {visit.notes && (
                                    <div className="sm:col-span-2">
                                        <dt className="text-slate-500">
                                            Catatan
                                        </dt>
                                        <dd className="whitespace-pre-line text-slate-900">
                                            {visit.notes}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" asChild>
                                    <a
                                        href={visit.whatsapp_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <MessageCircle />
                                        Hubungi via WhatsApp
                                    </a>
                                </Button>
                                {editBase && visit.can.update && (
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={`${editBase}/${visit.public_id}/ubah`}
                                        >
                                            <Pencil />
                                            Ubah
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </DialogContent>
            </Dialog>
            <Lightbox
                images={
                    visit?.photo_url
                        ? [{ url: visit.photo_url, name: visit.full_name }]
                        : []
                }
                index={zoom}
                onIndexChange={setZoom}
                title="Foto tamu"
            />
        </>
    );
}
