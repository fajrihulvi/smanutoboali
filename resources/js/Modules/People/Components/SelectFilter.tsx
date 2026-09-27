import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { cn } from '@/lib/utils';
import type { Option } from '@/Modules/People/types';

type Props = {
    label: string;
    allLabel: string;
    value: string | undefined;
    options: Option[];
    onChange: (value: string | undefined) => void;
    className?: string;
};

/**
 * Select filter dengan pilihan "Semua …" yang mengosongkan nilai.
 */
export function SelectFilter({
    label,
    allLabel,
    value,
    options,
    onChange,
    className,
}: Props) {
    return (
        <Select
            value={value ?? 'all'}
            onValueChange={(next) =>
                onChange(next === 'all' ? undefined : next)
            }
        >
            <SelectTrigger className={cn('w-44', className)} aria-label={label}>
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                <SelectItem value="all">{allLabel}</SelectItem>
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
