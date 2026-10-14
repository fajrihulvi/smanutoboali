import { Head, usePage } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { AppLogo } from '@/Components/app/AppLogo';
import { FlashToaster } from '@/Components/shared/FlashToaster';
import { portals } from '@/config/portals';
import type { AuthLayoutProps } from '@/types';

/**
 * Split-screen: panel gradient hijau (identitas portal) | form putih.
 * Di mobile panel hijau menjadi header ringkas.
 */
export default function AuthLayout({
    children,
    title,
    description,
    portal = null,
}: AuthLayoutProps) {
    const { school } = usePage().props;
    const meta = portal ? portals[portal] : null;
    const PortalIcon = meta?.icon ?? ShieldCheck;
    const heading = meta?.title ?? `Selamat datang di ${school.name}`;
    const tagline =
        meta?.tagline ??
        'Satu aplikasi untuk kepala sekolah, guru, operator, dan orang tua.';

    return (
        <>
            <Head title={title} />
            <div className="grid min-h-dvh bg-white lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
                {/* Panel hijau — desktop */}
                <aside className="relative hidden overflow-hidden bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 p-12 text-white lg:flex lg:flex-col">
                    <span className="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-brand-accent/20 blur-3xl" />
                    <span className="pointer-events-none absolute -bottom-32 -left-16 size-96 rounded-full bg-brand-500/25 blur-3xl" />
                    <span className="pointer-events-none absolute top-1/3 right-16 size-40 rounded-full border border-white/10" />
                    <span className="pointer-events-none absolute top-1/3 right-24 size-24 rounded-full border border-white/10" />

                    {/* Website publik dirender Blade (bukan Inertia): <a>, bukan <Link>, agar tidak tampil sebagai modal. */}
                    <a href="/" className="relative z-10 w-fit">
                        <AppLogo />
                    </a>

                    <div className="relative z-10 my-auto max-w-md">
                        {meta ? (
                            <span className="mb-6 flex size-14 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/20 backdrop-blur">
                                <PortalIcon className="size-7 text-brand-accent" />
                            </span>
                        ) : (
                            <img
                                src={school.logo_url}
                                alt={school.name}
                                className="mb-8 size-32 object-contain drop-shadow-xl"
                            />
                        )}
                        <h2 className="text-3xl leading-tight font-semibold">
                            {heading}
                        </h2>
                        <p className="mt-3 text-base text-white/70">
                            {tagline}
                        </p>
                    </div>

                    <p className="relative z-10 text-xs text-white/50">
                        © {new Date().getFullYear()} {school.name}
                    </p>
                </aside>

                <div className="flex flex-col">
                    {/* Header ringkas — mobile */}
                    <header className="relative overflow-hidden bg-gradient-to-br from-brand-950 to-brand-800 px-6 pt-6 pb-8 text-white lg:hidden">
                        <span className="pointer-events-none absolute -top-16 -right-10 size-48 rounded-full bg-brand-accent/20 blur-2xl" />
                        <a href="/" className="relative z-10 inline-flex">
                            <AppLogo />
                        </a>
                        <div className="relative z-10 mt-6 flex items-center gap-3">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                                <PortalIcon className="size-5 text-brand-accent" />
                            </span>
                            <p className="text-lg font-semibold">{heading}</p>
                        </div>
                    </header>

                    <main className="flex flex-1 items-center justify-center px-6 py-10 sm:px-10">
                        <div className="w-full max-w-sm">
                            <div className="mb-8">
                                <h1 className="text-2xl font-semibold tracking-tight text-slate-900">
                                    {title}
                                </h1>
                                {description && (
                                    <p className="mt-1.5 text-sm text-slate-500">
                                        {description}
                                    </p>
                                )}
                            </div>
                            {children}
                        </div>
                    </main>
                </div>
            </div>
            <FlashToaster />
        </>
    );
}
