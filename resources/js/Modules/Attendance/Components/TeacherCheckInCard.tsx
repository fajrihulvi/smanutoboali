import { router } from '@inertiajs/react';
import { LogIn, LogOut, MapPin, UserRoundX } from 'lucide-react';
import { useState } from 'react';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import { formatDate } from '@/lib/format';
import { toastFirstError } from '@/lib/toast-errors';
import type { StatusVariant } from '@/types';

export type SelfAttendance =
    | { hasTeacherProfile: false }
    | {
          hasTeacherProfile: true;
          date: string;
          today: {
              status: { value: string; label: string };
              check_in: string | null;
              check_out: string | null;
              note: string | null;
              source: string;
          } | null;
          canCheckIn: boolean;
          canCheckOut: boolean;
          lateAfter: string;
          needsLocation: boolean;
          month: {
              counts: Record<string, number>;
              days: number;
              percentage: number | null;
          };
      };

const VARIANT: Record<string, StatusVariant> = {
    HADIR: 'success',
    TERLAMBAT: 'warning',
    IZIN: 'info',
    SAKIT: 'brand',
    DINAS: 'neutral',
    ALPA: 'danger',
};

/**
 * Kartu "Presensi Saya" di dashboard guru: tombol masuk/pulang (sekali per hari) + ringkasan bulan ini.
 */
export function TeacherCheckInCard({ data }: { data: SelfAttendance }) {
    const [busy, setBusy] = useState<'in' | 'out' | null>(null);

    if (!data.hasTeacherProfile) {
        return (
            <section className="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <UserRoundX className="size-5 text-slate-400" />
                <p className="text-sm text-slate-600">
                    Akun belum terhubung ke data guru, presensi guru belum
                    tersedia.
                </p>
            </section>
        );
    }

    const send = (kind: 'in' | 'out', payload: Record<string, number> = {}) =>
        router.post(
            `/guru/kehadiran/${kind === 'in' ? 'masuk' : 'pulang'}`,
            payload,
            {
                preserveScroll: true,
                onStart: () => setBusy(kind),
                onFinish: () => setBusy(null),
                onError: (errors) => toastFirstError(errors),
            },
        );

    const checkIn = () => {
        if (!data.needsLocation) {
            send('in');

            return;
        }

        if (!('geolocation' in navigator)) {
            toastFirstError({ location: 'Browser tidak mendukung lokasi.' });

            return;
        }

        setBusy('in');
        navigator.geolocation.getCurrentPosition(
            (position) =>
                send('in', {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                }),
            () => {
                setBusy(null);
                toastFirstError({
                    location: 'Izinkan akses lokasi di browser untuk presensi.',
                });
            },
            { enableHighAccuracy: true, timeout: 15000 },
        );
    };

    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 className="text-base font-semibold text-slate-900">
                        Presensi Saya
                    </h2>
                    <p className="text-sm text-slate-500">
                        {formatDate(data.date, 'EEEE, d MMMM yyyy')} · terlambat
                        setelah {data.lateAfter}
                    </p>
                </div>
                {data.today ? (
                    <StatusBadge
                        variant={VARIANT[data.today.status.value] ?? 'neutral'}
                        dot
                    >
                        {data.today.status.label}
                    </StatusBadge>
                ) : (
                    <StatusBadge variant="neutral">Belum presensi</StatusBadge>
                )}
            </div>

            <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div className="rounded-xl bg-slate-50 p-3">
                    <dt className="text-xs text-slate-500">Masuk</dt>
                    <dd className="text-lg font-semibold text-slate-900 tabular-nums">
                        {data.today?.check_in ?? '—'}
                    </dd>
                </div>
                <div className="rounded-xl bg-slate-50 p-3">
                    <dt className="text-xs text-slate-500">Pulang</dt>
                    <dd className="text-lg font-semibold text-slate-900 tabular-nums">
                        {data.today?.check_out ?? '—'}
                    </dd>
                </div>
            </dl>

            {data.today?.note && (
                <p className="mt-3 text-sm text-slate-600">
                    Catatan: {data.today.note}
                </p>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-2">
                {data.canCheckIn && (
                    <Button onClick={checkIn} disabled={busy !== null}>
                        {busy === 'in' ? <Spinner /> : <LogIn />}
                        Presensi Masuk
                    </Button>
                )}
                {data.canCheckOut && (
                    <Button
                        variant="outline"
                        onClick={() => send('out')}
                        disabled={busy !== null}
                    >
                        {busy === 'out' ? <Spinner /> : <LogOut />}
                        Presensi Pulang
                    </Button>
                )}
                {data.needsLocation && data.canCheckIn && (
                    <span className="flex items-center gap-1 text-xs text-slate-500">
                        <MapPin className="size-3.5" />
                        Memerlukan izin lokasi
                    </span>
                )}
            </div>

            <p className="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500 tabular-nums">
                Bulan ini: {data.month.days} hari tercatat
                {data.month.percentage !== null &&
                    ` · kehadiran ${data.month.percentage.toLocaleString('id-ID')}%`}
                {data.month.counts.TERLAMBAT > 0 &&
                    ` · terlambat ${data.month.counts.TERLAMBAT}×`}
            </p>
        </section>
    );
}
