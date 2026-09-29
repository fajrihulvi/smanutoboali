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
        /** Error validasi per field (pesan pertama), bila respons 422. */
        public readonly errors: Record<string, string> = {},
    ) {
        super(message);
    }
}

/** Ambil pesan pertama per field dari respons validasi Laravel. */
export function fieldErrors(body: unknown): Record<string, string> {
    const errors = (body as { errors?: Record<string, string[]> } | null)
        ?.errors;

    return errors
        ? Object.fromEntries(
              Object.entries(errors).map(([key, messages]) => [
                  key,
                  messages[0] ?? '',
              ]),
          )
        : {};
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
            fieldErrors(data),
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

/** Nama file dari header Content-Disposition (attachment; filename="..."). */
export function filenameFromDisposition(
    header: string | null,
    fallback: string,
): string {
    const match = header?.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);

    return match ? decodeURIComponent(match[1]) : fallback;
}

export type DownloadResult =
    { queued: false; filename: string } | { queued: true; message: string };

/**
 * POST lalu unduh respons berupa file (blob). Bila server membalas JSON (mis. 202 diproses
 * antrean), kembalikan pesannya. Error validasi dilempar sebagai HttpError per field.
 */
export async function postForDownload(
    url: string,
    body: Record<string, unknown>,
    fallbackName = 'unduhan',
): Promise<DownloadResult> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    const type = response.headers.get('Content-Type') ?? '';

    if (!response.ok) {
        const data: unknown = type.includes('json')
            ? await response.json().catch(() => null)
            : null;

        throw new HttpError(
            errorMessage(response.status, data),
            response.status,
            fieldErrors(data),
        );
    }

    if (type.includes('json')) {
        const data = (await response.json()) as { message?: string };

        return { queued: true, message: data.message ?? 'Sedang diproses.' };
    }

    const filename = filenameFromDisposition(
        response.headers.get('Content-Disposition'),
        fallbackName,
    );
    const href = URL.createObjectURL(await response.blob());
    const link = document.createElement('a');
    link.href = href;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(href), 1000);

    return { queued: false, filename };
}
