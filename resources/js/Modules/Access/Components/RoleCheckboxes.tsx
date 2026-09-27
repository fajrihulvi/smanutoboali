import InputError from '@/Components/input-error';
import { Checkbox } from '@/Components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { Role } from '@/types';

type Props = {
    roles: Role[];
    value: string[];
    onChange: (value: string[]) => void;
    error?: string;
    /** Role yang tidak boleh dicabut (mis. operator milik akun sendiri). */
    locked?: string[];
};

export function RoleCheckboxes({
    roles,
    value,
    onChange,
    error,
    locked = [],
}: Props) {
    const toggle = (code: string, checked: boolean) => {
        onChange(
            checked ? [...value, code] : value.filter((item) => item !== code),
        );
    };

    return (
        <fieldset className="grid gap-2">
            <legend className="mb-2 text-sm font-medium text-slate-900">
                Role
            </legend>
            <div className="grid gap-2 sm:grid-cols-2">
                {roles.map((role) => {
                    const checked = value.includes(role.code);
                    const isLocked = locked.includes(role.code);
                    const id = `role-${role.code}`;

                    return (
                        <label
                            key={role.code}
                            htmlFor={id}
                            className={cn(
                                'flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-2.5 text-sm transition-colors duration-150',
                                checked
                                    ? 'border-brand-300 bg-brand-50 text-brand-900'
                                    : 'border-slate-200 text-slate-700 hover:bg-slate-50',
                                isLocked && 'cursor-not-allowed opacity-70',
                            )}
                        >
                            <Checkbox
                                id={id}
                                checked={checked}
                                disabled={isLocked}
                                onCheckedChange={(state) =>
                                    toggle(role.code, state === true)
                                }
                            />
                            {role.label}
                        </label>
                    );
                })}
            </div>
            <InputError message={error} />
        </fieldset>
    );
}
