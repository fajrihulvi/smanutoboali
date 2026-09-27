import type { Auth, Flash } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            flash: Flash;
            notifications: { unread: number };
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
