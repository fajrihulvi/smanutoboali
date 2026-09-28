import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { cn } from '@/lib/utils';

type Props = {
    name: string;
    online?: boolean;
    className?: string;
};

/**
 * Avatar inisial lawan bicara dengan titik hijau bila sedang online (presence).
 */
export function ChatAvatar({ name, online = false, className }: Props) {
    return (
        <span className="relative inline-flex shrink-0">
            <PersonAvatar name={name} className={cn('size-10', className)} />
            {online && (
                <span
                    className="absolute right-0 bottom-0 size-3 rounded-full bg-emerald-500 ring-2 ring-white"
                    aria-label="Sedang online"
                />
            )}
        </span>
    );
}
