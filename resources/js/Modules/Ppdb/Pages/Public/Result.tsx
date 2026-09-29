import { useForm } from '@inertiajs/react';
import { CheckCircle2, XCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import TextLink from '@/Components/text-link';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import AuthLayout from '@/Layouts/AuthLayout';

type Props = {
    result: {
        number: string;
        name: string;
        period: string;
        accepted: boolean;
    } | null;
};

export default function PpdbResult({ result }: Props) {
    const form = useForm({ number: '', birth_date: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/ppdb/hasil');
    };

    return (
        <AuthLayout
            portal="applicant"
            title="Cek hasil seleksi PPDB"
            description="Masukkan nomor pendaftaran dan tanggal lahir calon siswa."
        >
            {result && (
                <div
                    className={
                        result.accepted
                            ? 'mb-6 rounded-xl bg-emerald-50 p-4 text-emerald-900'
                            : 'mb-6 rounded-xl bg-rose-50 p-4 text-rose-900'
                    }
                    role="status"
                >
                    <p className="flex items-center gap-2 font-semibold">
                        {result.accepted ? (
                            <CheckCircle2 className="size-5" />
                        ) : (
                            <XCircle className="size-5" />
                        )}
                        {result.accepted ? 'DITERIMA' : 'Belum diterima'}
                    </p>
                    <p className="mt-1 text-sm">
                        {result.name} · {result.number} · {result.period}
                    </p>
                    {result.accepted && (
                        <p className="mt-2 text-sm">
                            Lakukan daftar ulang melalui portal PPDB.
                        </p>
                    )}
                </div>
            )}
            <form onSubmit={submit} className="space-y-5" noValidate>
                <div className="grid gap-2">
                    <Label htmlFor="number">Nomor pendaftaran</Label>
                    <Input
                        id="number"
                        value={form.data.number}
                        onChange={(e) => form.setData('number', e.target.value)}
                        placeholder="PPDB-2027-0001"
                        autoFocus
                        aria-invalid={!!form.errors.number}
                    />
                    <InputError message={form.errors.number} />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="birth_date">
                        Tanggal lahir calon siswa
                    </Label>
                    <Input
                        id="birth_date"
                        type="date"
                        value={form.data.birth_date}
                        onChange={(e) =>
                            form.setData('birth_date', e.target.value)
                        }
                        aria-invalid={!!form.errors.birth_date}
                    />
                    <InputError message={form.errors.birth_date} />
                </div>
                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    Cek hasil
                </Button>
            </form>
            <p className="mt-6 text-center text-sm text-slate-600">
                Sudah punya akun?{' '}
                <TextLink href="/ppdb/masuk">Masuk portal PPDB</TextLink>
            </p>
        </AuthLayout>
    );
}
