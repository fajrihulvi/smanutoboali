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
            school: { name: string; level: string; logo_url: string | null };
            activePeriod: ActivePeriod | null;
            sidebarOpen: boolean;
            /** null = realtime nonaktif (tanpa kunci Pusher / tamu) → UI memakai fallback. */
            realtime: RealtimeConfig | null;
            [key: string]: unknown;
        };
    }
}
