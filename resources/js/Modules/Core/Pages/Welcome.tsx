import { Head, Link } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';

export default function Welcome() {
    return (
        <>
            <Head title="Selamat Datang" />
            <main className="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 text-center">
                <div className="mb-6 flex size-14 items-center justify-center rounded-2xl bg-brand-600 text-white">
                    <GraduationCap className="size-7" />
                </div>
                <h1 className="text-3xl font-semibold text-slate-900">
                    Skoolia
                </h1>
                <p className="mt-2 max-w-md text-sm text-slate-600">
                    Sistem Informasi Sekolah Terintegrasi
                </p>
                <Link
                    href="/login"
                    className="mt-8 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-700"
                >
                    Masuk
                </Link>
            </main>
        </>
    );
}
