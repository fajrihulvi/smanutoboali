import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { ActivityMeta } from '@/Modules/Activity/Components/ActivityMeta';
import { PhotoStrip } from '@/Modules/Activity/Components/PhotoStrip';
import type { ActivityRow } from '@/Modules/Activity/types';

type Props = {
    activity: ActivityRow | null;
    onClose: () => void;
    /** Prefix URL kelola (guru/operator) untuk tombol ubah. */
    manageBase?: string;
};

export function ActivityDetailDialog({ activity, onClose, manageBase }: Props) {
    return (
        <Dialog
            open={activity !== null}
            onOpenChange={(open) => !open && onClose()}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                {activity && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{activity.title}</DialogTitle>
                        </DialogHeader>
                        <ActivityMeta activity={activity} />
                        {activity.description && (
                            <p className="text-sm whitespace-pre-line text-slate-700">
                                {activity.description}
                            </p>
                        )}
                        <PhotoStrip activity={activity} />
                        {manageBase && activity.can.update && (
                            <Button variant="outline" asChild>
                                <Link
                                    href={`${manageBase}/${activity.public_id}/ubah`}
                                >
                                    <Pencil />
                                    Ubah kegiatan
                                </Link>
                            </Button>
                        )}
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
