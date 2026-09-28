import type { StatusVariant } from '@/types';

export type TeacherStatusValue =
    'HADIR' | 'TERLAMBAT' | 'IZIN' | 'SAKIT' | 'DINAS' | 'ALPA';

export type TeacherRecord = {
    status: { value: TeacherStatusValue; label: string };
    check_in: string | null;
    check_out: string | null;
    note: string | null;
    source: 'MANDIRI' | 'OPERATOR';
};

export const TEACHER_STATUS_VARIANT: Record<TeacherStatusValue, StatusVariant> =
    {
        HADIR: 'success',
        TERLAMBAT: 'warning',
        IZIN: 'info',
        SAKIT: 'brand',
        DINAS: 'neutral',
        ALPA: 'danger',
    };

/** Status yang punya jam masuk/pulang. */
export const ON_SITE: TeacherStatusValue[] = ['HADIR', 'TERLAMBAT'];
