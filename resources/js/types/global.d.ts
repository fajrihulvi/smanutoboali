import type { Auth, Flash } from '@/types/auth';
import type { ActivePeriod } from '@/types/period';
import type { RealtimeConfig } from '@/types/realtime';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: Flash;
            notifications: { unread: number };
            chat: { unread: number };
            /** Portal orang tua: daftar anak + anak aktif (session); null untuk role lain. */
            guardian: {
                childOptions: {
                    public_id: string;
                    name: string;
                    photo_url: string | null;
                }[];
                activeChild: string | null;
            } | null;
            school: { name: string; level: string; logo_url: string };
            activePeriod: ActivePeriod | null;
            sidebarOpen: boolean;
            /** null = realtime nonaktif (tanpa kunci Pusher / tamu) → UI memakai fallback. */
            realtime: RealtimeConfig | null;
            [key: string]: unknown;
        };
    }
}
