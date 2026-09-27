import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { staffAndParentPortals } from '@/config/portals';
import AuthLayout from '@/Layouts/AuthLayout';

export default function PortalSelect() {
    return (
        <AuthLayout
            title="Pilih portal masuk"
            description="Masuk sesuai peran Anda di sekolah."
        >
            <ul className="space-y-3">
                {staffAndParentPortals.map((portal) => (
                    <li key={portal.role}>
                        <Link
                            href={portal.loginPath}
                            className="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300 hover:bg-brand-50/60"
                        >
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-colors duration-150 group-hover:bg-brand-600 group-hover:text-white">
                                <portal.icon className="size-5" />
                            </span>
                            <span className="min-w-0 flex-1">
                                <span className="block text-sm font-semibold text-slate-900">
                                    {portal.title}
                                </span>
                                <span className="mt-0.5 block text-xs text-slate-500">
                                    {portal.tagline}
                                </span>
                            </span>
                            <ChevronRight className="size-4 shrink-0 text-slate-400 transition-transform duration-150 group-hover:translate-x-0.5 group-hover:text-brand-600" />
                        </Link>
                    </li>
                ))}
            </ul>
        </AuthLayout>
    );
}
