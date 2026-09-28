import { Input } from '@/Components/ui/input';
import { cn } from '@/lib/utils';

type Props = {
    id?: string;
    value: number | '';
    onChange: (value: number | '') => void;
    invalid?: boolean;
    className?: string;
    placeholder?: string;
};

const format = new Intl.NumberFormat('id-ID');

/**
 * Input nominal rupiah (integer) dengan pemisah ribuan saat diketik.
 */
export function RupiahInput({
    id,
    value,
    onChange,
    invalid,
    className,
    placeholder = '0',
}: Props) {
    return (
        <div className={cn('relative', className)}>
            <span className="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-slate-500">
                Rp
            </span>
            <Input
                id={id}
                inputMode="numeric"
                className="pl-9 tabular-nums"
                value={value === '' ? '' : format.format(value)}
                placeholder={placeholder}
                aria-invalid={invalid}
                onChange={(event) => {
                    const digits = event.target.value.replace(/\D/g, '');
                    onChange(digits === '' ? '' : Number(digits.slice(0, 12)));
                }}
            />
        </div>
    );
}
