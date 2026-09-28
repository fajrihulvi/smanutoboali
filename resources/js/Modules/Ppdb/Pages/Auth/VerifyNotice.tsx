import { router, useForm } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import AuthLayout from '@/Layouts/AuthLayout';

export default function VerifyNotice({ email }: { email: string }) {
    const form = useForm({});

    const resend = (event: FormEvent) => {
        event.preventDefault();
        form.post('/ppdb/akun/verifikasi-email', { preserveScroll: true });
    };

    return (
        <AuthLayout
            portal="applicant"
            title="Verifikasi email Anda"
            description="Satu langkah lagi sebelum mendaftarkan calon siswa."
        >
            <div className="space-y-5">
                <div className="flex items-start gap-3 rounded-xl bg-brand-50 p-4 text-sm text-brand-900">
                    <MailCheck className="mt-0.5 size-5 shrink-0" />
                    <p>
                        Kami mengirim tautan verifikasi ke{' '}
                        <strong>{email}</strong>. Buka email tersebut dan klik
                        tautannya. Periksa folder spam bila belum masuk.
                    </p>
                </div>
                <form onSubmit={resend}>
                    <Button
                        type="submit"
                        variant="outline"
                        className="w-full"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        Kirim ulang tautan
                    </Button>
                </form>
                <Button
                    variant="ghost"
                    className="w-full"
                    onClick={() => router.post('/logout')}
                >
                    Keluar
                </Button>
            </div>
        </AuthLayout>
    );
}
