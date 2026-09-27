import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { staffAndParentPortals } from '@/config/portals';
import AuthLayout from '@/Layouts/AuthLayout';
import type { RoleCode } from '@/types';

export default function StyleGuideAuth({
    portal,
}: {
    portal: RoleCode | null;
}) {
    return (
        <AuthLayout
            portal={portal}
            title="Masuk ke akun Anda"
            description="Pratinjau AuthLayout. Pilih portal di bawah untuk melihat variasi panel."
        >
            <form
                className="space-y-5"
                onSubmit={(event) => event.preventDefault()}
            >
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        placeholder="nama@sekolah.sch.id"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        placeholder="••••••••"
                    />
                </div>
                <div className="flex items-center gap-2">
                    <Checkbox id="remember" />
                    <Label
                        htmlFor="remember"
                        className="font-normal text-slate-600"
                    >
                        Ingat saya
                    </Label>
                </div>
                <Button type="submit" className="w-full">
                    Masuk
                </Button>
            </form>

            <div className="mt-8 flex flex-wrap gap-2">
                {staffAndParentPortals.map((item) => (
                    <Button key={item.role} variant="outline" size="sm" asChild>
                        <Link href={`/style-guide/login?portal=${item.role}`}>
                            {item.title}
                        </Link>
                    </Button>
                ))}
            </div>
        </AuthLayout>
    );
}
