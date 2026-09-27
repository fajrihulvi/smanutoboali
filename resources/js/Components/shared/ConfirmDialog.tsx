import { AlertTriangle, HelpCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Spinner } from '@/Components/ui/spinner';
import { cn } from '@/lib/utils';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: ReactNode;
    confirmLabel?: string;
    cancelLabel?: string;
    variant?: 'default' | 'danger';
    processing?: boolean;
    onConfirm: () => void;
};

export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = 'Ya, lanjutkan',
    cancelLabel = 'Batal',
    variant = 'default',
    processing = false,
    onConfirm,
}: Props) {
    const Icon = variant === 'danger' ? AlertTriangle : HelpCircle;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader className="items-center text-center sm:items-start sm:text-left">
                    <span
                        className={cn(
                            'mb-2 flex size-11 items-center justify-center rounded-full',
                            variant === 'danger'
                                ? 'bg-rose-100 text-rose-600'
                                : 'bg-brand-50 text-brand-600',
                        )}
                    >
                        <Icon className="size-5" />
                    </span>
                    <DialogTitle>{title}</DialogTitle>
                    {description && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={processing}
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        type="button"
                        variant={
                            variant === 'danger' ? 'destructive' : 'default'
                        }
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
