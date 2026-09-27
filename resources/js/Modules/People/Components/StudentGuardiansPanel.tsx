import { router } from '@inertiajs/react';
import { Link2Off, Plus, Star, Users } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { EmptyState } from '@/Components/shared/EmptyState';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { toastFirstError } from '@/lib/toast-errors';
import { AttachGuardianDialog } from '@/Modules/People/Components/AttachGuardianDialog';
import type {
    Option,
    StudentGuardian,
    StudentRow,
} from '@/Modules/People/types';

type Props = {
    student: StudentRow;
    relationships: Option[];
};

export function StudentGuardiansPanel({ student, relationships }: Props) {
    const guardians = student.guardians ?? [];
    const base = `/operator/master-data/siswa/${student.public_id}/orang-tua`;
    const [attachOpen, setAttachOpen] = useState(false);
    const [detaching, setDetaching] = useState<StudentGuardian | null>(null);
    const [processing, setProcessing] = useState(false);

    const makePrimary = (guardian: StudentGuardian) => {
        router.post(
            `${base}/${guardian.public_id}/utama`,
            {},
            {
                preserveScroll: true,
                onError: (errors) => toastFirstError(errors),
            },
        );
    };

    const detach = () => {
        if (!detaching) {
            return;
        }

        router.delete(`${base}/${detaching.public_id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onError: (errors) => toastFirstError(errors),
            onFinish: () => {
                setProcessing(false);
                setDetaching(null);
            },
        });
    };

    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-base font-semibold text-slate-900">
                        Orang tua / wali
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Orang tua utama menerima notifikasi dan tagihan. Satu
                        orang tua bisa terhubung ke beberapa anak.
                    </p>
                </div>
                <Button onClick={() => setAttachOpen(true)}>
                    <Plus />
                    Hubungkan orang tua
                </Button>
            </div>

            {guardians.length === 0 ? (
                <EmptyState
                    icon={Users}
                    title="Belum ada orang tua"
                    description="Hubungkan minimal satu orang tua/wali agar portal orang tua dapat dipakai."
                />
            ) : (
                <ul className="mt-5 grid gap-3 md:grid-cols-2">
                    {guardians.map((guardian) => (
                        <li
                            key={guardian.public_id}
                            className="rounded-xl border border-slate-200 p-4"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0">
                                    <p className="font-medium text-slate-900">
                                        {guardian.name}
                                    </p>
                                    <div className="mt-1 flex flex-wrap gap-1">
                                        <StatusBadge variant="neutral">
                                            {guardian.relationship.label}
                                        </StatusBadge>
                                        {guardian.is_primary && (
                                            <StatusBadge variant="brand" dot>
                                                Utama
                                            </StatusBadge>
                                        )}
                                        {guardian.has_account ? (
                                            <StatusBadge variant="success">
                                                Punya akun
                                            </StatusBadge>
                                        ) : (
                                            <StatusBadge variant="warning">
                                                Belum ada akun
                                            </StatusBadge>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <p className="mt-3 text-sm text-slate-600 tabular-nums">
                                {[guardian.phone, guardian.email]
                                    .filter(Boolean)
                                    .join(' · ') || 'Kontak belum diisi'}
                            </p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {!guardian.is_primary && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => makePrimary(guardian)}
                                    >
                                        <Star />
                                        Jadikan utama
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                    onClick={() => setDetaching(guardian)}
                                >
                                    <Link2Off />
                                    Lepaskan
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <AttachGuardianDialog
                open={attachOpen}
                onOpenChange={setAttachOpen}
                studentPublicId={student.public_id}
                relationships={relationships}
                linked={guardians.map((guardian) => guardian.public_id)}
            />

            <ConfirmDialog
                open={detaching !== null}
                onOpenChange={(open) => !open && setDetaching(null)}
                variant="danger"
                title={`Lepaskan ${detaching?.name ?? ''}?`}
                description="Orang tua ini tidak lagi bisa melihat data siswa di portal orang tua. Data orang tuanya tidak dihapus."
                confirmLabel="Ya, lepaskan"
                processing={processing}
                onConfirm={detach}
            />
        </section>
    );
}
