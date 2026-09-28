/**
 * Pembatas frekuensi sederhana: `ready()` true paling sering sekali per `intervalMs`.
 */
export function createThrottle(
    intervalMs: number,
    now: () => number = Date.now,
) {
    let last = Number.NEGATIVE_INFINITY;

    return {
        ready(): boolean {
            const current = now();

            if (current - last < intervalMs) {
                return false;
            }

            last = current;

            return true;
        },
    };
}
