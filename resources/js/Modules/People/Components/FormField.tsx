import type { ReactNode } from 'react';
import InputError from '@/Components/input-error';
import { Label } from '@/Components/ui/label';

type Props = {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    children: ReactNode;
};

export function FormField({ id, label, error, hint, children }: Props) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-slate-500">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

export function FormCard({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-base font-semibold text-slate-900">{title}</h2>
            {description && (
                <p className="mt-1 text-sm text-slate-500">{description}</p>
            )}
            <div className="mt-5 space-y-5">{children}</div>
        </section>
    );
}
