type SnapCallbacks = {
    onSuccess?: () => void;
    onPending?: () => void;
    onError?: () => void;
    onClose?: () => void;
};

type Snap = { pay: (token: string, callbacks: SnapCallbacks) => void };

declare global {
    interface Window {
        snap?: Snap;
    }
}

let loading: Promise<Snap> | null = null;

/**
 * Muat snap.js Midtrans sekali (hanya saat orang tua menekan "Bayar online").
 */
export function loadSnap(scriptUrl: string, clientKey: string): Promise<Snap> {
    if (window.snap) {
        return Promise.resolve(window.snap);
    }

    loading ??= new Promise<Snap>((resolve, reject) => {
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.dataset.clientKey = clientKey;
        script.async = true;
        script.onload = () =>
            window.snap
                ? resolve(window.snap)
                : reject(new Error('Snap tidak tersedia.'));
        script.onerror = () => {
            loading = null;
            reject(new Error('Gagal memuat halaman pembayaran.'));
        };
        document.head.appendChild(script);
    });

    return loading;
}
