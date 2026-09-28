/**
 * fetch JSON ke endpoint Laravel dengan sesi & token CSRF (cookie XSRF-TOKEN).
 * Untuk endpoint kecil non-Inertia (mis. dropdown notifikasi, simpan sel nilai).
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/** Error dengan pesan yang layak ditampilkan (pesan validasi pertama dari server bila ada). */
export class HttpError extends Error {
    constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);
    }
}

/** Pesan pertama dari respons error Laravel ({ message, errors }). */
export function errorMessage(status: number, body: unknown): string {
    if (body && typeof body === 'object') {
        const { errors, message } = body as {
            errors?: Record<string, string[]>;
            message?: string;
        };
        const first = errors ? Object.values(errors)[0]?.[0] : undefined;

        if (first) {
            return first;
        }

        if (status < 500 && message) {
            return message;
        }
    }

    return status === 419
        ? 'Sesi berakhir. Muat ulang halaman.'
        : `Permintaan gagal (${status}).`;
}

/**
 * Body objek dikirim sebagai JSON; FormData (unggah file) dikirim apa adanya
 * sehingga browser mengisi boundary multipart sendiri.
 */
export async function sendJson<T>(
    url: string,
    method: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' = 'GET',
    body?: Record<string, unknown> | FormData,
): Promise<T> {
    const isForm = body instanceof FormData;
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
            ...(body && !isForm ? { 'Content-Type': 'application/json' } : {}),
        },
        body: isForm ? body : body ? JSON.stringify(body) : undefined,
    });

    const data: unknown = await response.json().catch(() => null);

    if (!response.ok) {
        throw new HttpError(
            errorMessage(response.status, data),
            response.status,
        );
    }

    return data as T;
}

export async function requestJson<T>(
    url: string,
    method: 'GET' | 'POST' = 'GET',
): Promise<T> {
    return sendJson<T>(url, method);
}
