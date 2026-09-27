import { Link } from '@inertiajs/react';
import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

type Props = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: Props) {
    return (
        <Link
            className={cn(
                'font-medium text-brand-600 underline-offset-4 transition-colors duration-150 hover:text-brand-700 hover:underline',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
