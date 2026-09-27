import type { Auth, Flash } from '@/types/auth';
import type { ActivePeriod } from '@/types/period';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: Flash;
            notifications: { unread: number };
            school: { name: string; level: string; logo_url: string | null };
            activePeriod: ActivePeriod | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
