import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { Toaster, toast } from 'sonner';

/**
 * Toast (sonner) kanan atas yang membaca flash message Inertia
 * (session 'success' / 'error' dari backend).
 */
export function FlashToaster() {
    const { flash } = usePage().props;

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    return <Toaster position="top-right" richColors closeButton />;
}
