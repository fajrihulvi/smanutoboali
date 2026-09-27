import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';

type Props = {
    name: string;
    photoUrl?: string | null;
    className?: string;
};

/**
 * Foto orang (guru/siswa) atau inisial berlatar brand bila tidak ada foto.
 */
export function PersonAvatar({ name, photoUrl, className }: Props) {
    const getInitials = useInitials();

    return (
        <Avatar className={cn('size-9', className)}>
            {photoUrl && (
                <AvatarImage
                    src={photoUrl}
                    alt={name}
                    className="object-cover"
                />
            )}
            <AvatarFallback className="bg-brand-100 text-xs font-semibold text-brand-700">
                {getInitials(name)}
            </AvatarFallback>
        </Avatar>
    );
}
