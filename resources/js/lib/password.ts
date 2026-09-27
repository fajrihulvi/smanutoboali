const ALPHABET =
    'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';

/**
 * Password acak (tanpa karakter yang mudah tertukar: 0/O, 1/l/I).
 */
export function generatePassword(length = 12): string {
    const values = new Uint32Array(length);
    crypto.getRandomValues(values);

    return Array.from(
        values,
        (value) => ALPHABET[value % ALPHABET.length],
    ).join('');
}
