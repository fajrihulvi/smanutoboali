import { Link, useForm } from '@inertiajs/react';
import { ExternalLink, Unlink, UserPlus } from 'lucide-react';
import type { FormEvent } from 'react';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Spinner } from '@/Components/ui/spinner';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import { FormCard, FormField } from '@/Modules/People/Components/FormField';
import type { LinkedAccount } from '@/Modules/People/types';

type Props = {
    account: LinkedAccount;
    /** Endpoint akun: POST menautkan, DELETE melepas. Mis. /operator/master-data/guru/{id}/akun */
    linkUrl: string;
    defaultEmail?: string | null;
};

/**
 * Status akun login data guru/orang tua (bisa dilepas); bila belum ada, form membuat/menautkan akun.
 */
export function AccountSection({ account, linkUrl, defaultEmail }: Props) {
    const form = useForm({ account_email: defaultEmail ?? '' });
    const { confirmDelete, dialog } = useConfirmDelete();

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(linkUrl, { preserveScroll: true });
    };

    if (account) {
        return (
            <FormCard
                title="Akun login"
                description="Pengelolaan password & status akun ada di User Management."
            >
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                    <div>
                        <p className="text-sm font-medium break-all text-slate-900">
                            {account.email}
                        </p>
                        <div className="mt-1">
                            {account.is_active ? (
                                <StatusBadge variant="success" dot>
                                    Akun aktif
                                </StatusBadge>
                            ) : (
                                <StatusBadge variant="neutral" dot>
                                    Akun nonaktif
                                </StatusBadge>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link
                                href={`/operator/users?search=${encodeURIComponent(account.email)}`}
                            >
                                <ExternalLink />
                                Kelola akun
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            className="text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                            onClick={() =>
                                confirmDelete({
                                    url: linkUrl,
                                    title: `Lepas akun ${account.email}?`,
                                    description:
                                        'Akun tidak dihapus: akun tetap ada di User Management dan bisa ditautkan lagi, dinonaktifkan, atau dihapus.',
                                    confirmLabel: 'Ya, lepas akun',
                                })
                            }
                        >
                            <Unlink />
                            Lepas akun
                        </Button>
                    </div>
                </div>
                {dialog}
            </FormCard>
        );
    }

    return (
        <FormCard
            title="Akun login"
            description="Belum ada akun. Buat akun baru (password sementara dibuat otomatis) atau tautkan ke akun yang sudah ada dengan email yang sama."
        >
            <form
                onSubmit={submit}
                className="flex flex-col gap-3 sm:flex-row sm:items-start"
            >
                <div className="flex-1">
                    <FormField
                        id="account_email"
                        label="Email akun"
                        error={form.errors.account_email}
                    >
                        <Input
                            id="account_email"
                            type="email"
                            value={form.data.account_email}
                            onChange={(event) =>
                                form.setData(
                                    'account_email',
                                    event.target.value,
                                )
                            }
                            aria-invalid={!!form.errors.account_email}
                        />
                    </FormField>
                </div>
                <Button
                    type="submit"
                    className="sm:mt-[1.375rem]"
                    disabled={form.processing}
                >
                    {form.processing ? <Spinner /> : <UserPlus />}
                    Buat akun
                </Button>
            </form>
        </FormCard>
    );
}
