import { Head, Link, useForm } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Spinner } from '@/Components/ui/spinner';
import AppLayout from '@/Layouts/AppLayout';
import { cn } from '@/lib/utils';
import type { PermissionGroup, RoleDetail } from '@/Modules/Access/types';

type Props = {
    role: RoleDetail;
    groups: PermissionGroup[];
};

/** Permission yang tidak boleh dicabut dari role tertentu (dijaga juga di server). */
const LOCKED: Record<string, string[]> = {
    operator: ['role.manage'],
};

export default function RolesEdit({ role, groups }: Props) {
    const form = useForm({ permissions: role.permissions });
    const locked = LOCKED[role.code] ?? [];
    const selected = new Set(form.data.permissions);

    const setMany = (names: string[], checked: boolean) => {
        const next = new Set(form.data.permissions);

        names.forEach((name) => {
            if (checked) {
                next.add(name);
            } else if (!locked.includes(name)) {
                next.delete(name);
            }
        });

        form.setData('permissions', [...next].sort());
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/operator/roles/${role.code}`, { preserveScroll: true });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Role & Permission', href: '/operator/roles' },
                {
                    title: role.label,
                    href: `/operator/roles/${role.code}/edit`,
                },
            ]}
        >
            <Head title={`Permission ${role.label}`} />

            <PageHeader
                title={`Permission ${role.label}`}
                description="Centang hak akses yang dimiliki role ini. Cakupan data (mis. hanya kelas yang diajar) tetap dibatasi sistem."
            />

            <form onSubmit={submit} className="space-y-4">
                <InputError message={form.errors.permissions} />

                <div className="grid gap-4 lg:grid-cols-2">
                    {groups.map((group) => {
                        const names = group.permissions.map(
                            (permission) => permission.name,
                        );
                        const checkedCount = names.filter((name) =>
                            selected.has(name),
                        ).length;
                        const allChecked = checkedCount === names.length;

                        return (
                            <fieldset
                                key={group.group}
                                className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                            >
                                <div className="mb-3 flex items-center justify-between gap-3">
                                    <legend className="text-sm font-semibold text-slate-900">
                                        {group.group}
                                        <span className="ml-2 text-xs font-normal text-slate-400 tabular-nums">
                                            {checkedCount}/{names.length}
                                        </span>
                                    </legend>
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setMany(names, !allChecked)
                                        }
                                        className="text-xs font-medium text-brand-600 hover:text-brand-700"
                                    >
                                        {allChecked
                                            ? 'Hapus semua'
                                            : 'Pilih semua'}
                                    </button>
                                </div>

                                <div className="space-y-1">
                                    {group.permissions.map((permission) => {
                                        const isLocked = locked.includes(
                                            permission.name,
                                        );
                                        const id = `perm-${permission.name}`;

                                        return (
                                            <label
                                                key={permission.name}
                                                htmlFor={id}
                                                className={cn(
                                                    'flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 transition-colors duration-150 hover:bg-slate-50',
                                                    isLocked &&
                                                        'cursor-not-allowed',
                                                )}
                                            >
                                                <Checkbox
                                                    id={id}
                                                    checked={selected.has(
                                                        permission.name,
                                                    )}
                                                    disabled={isLocked}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        setMany(
                                                            [permission.name],
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm text-slate-800">
                                                        {permission.label}
                                                    </span>
                                                    <span className="block text-xs text-slate-400">
                                                        {permission.name}
                                                    </span>
                                                </span>
                                                {isLocked && (
                                                    <Lock
                                                        className="size-4 text-slate-400"
                                                        aria-label="Tidak dapat dicabut"
                                                    />
                                                )}
                                            </label>
                                        );
                                    })}
                                </div>
                            </fieldset>
                        );
                    })}
                </div>

                <div className="sticky bottom-0 flex items-center gap-2 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-sm backdrop-blur">
                    <p className="mr-auto text-sm text-slate-500 tabular-nums">
                        {form.data.permissions.length}
                        <span className="hidden sm:inline">
                            {' '}
                            permission
                        </span>{' '}
                        dipilih
                    </p>
                    <Button type="button" variant="outline" asChild>
                        <Link href="/operator/roles">Kembali</Link>
                    </Button>
                    <Button
                        type="submit"
                        disabled={form.processing || !form.isDirty}
                    >
                        {form.processing && <Spinner />}
                        Simpan
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
