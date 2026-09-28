import { describe, expect, it } from 'vitest';
import { createThrottle } from '@/lib/throttle';

describe('createThrottle', () => {
    it('mengizinkan paling sering sekali per interval', () => {
        let time = 0;
        const throttle = createThrottle(30_000, () => time);

        expect(throttle.ready()).toBe(true);
        time = 10_000;
        expect(throttle.ready()).toBe(false);
        time = 30_000;
        expect(throttle.ready()).toBe(true);
        time = 59_999;
        expect(throttle.ready()).toBe(false);
    });
});
