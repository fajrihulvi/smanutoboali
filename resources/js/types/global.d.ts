import type { Auth, Flash } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: Flash;
            notifications: { unread: number };
            school: { name: string; logo_url: string | null };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
