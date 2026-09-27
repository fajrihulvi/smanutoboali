/**
 * fetch JSON ke endpoint Laravel dengan sesi & token CSRF (cookie XSRF-TOKEN).
 * Untuk endpoint kecil non-Inertia (mis. dropdown notifikasi).
 */
function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function requestJson<T>(
    url: string,
    method: 'GET' | 'POST' = 'GET',
): Promise<T> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
    });

    if (!response.ok) {
        throw new Error(`Permintaan gagal (${response.status})`);
    }

    return (await response.json()) as T;
}
