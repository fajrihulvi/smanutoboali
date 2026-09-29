import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, PartyPopper } from 'lucide-react';
import type { FormEvent } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import type { Option } from '@/Modules/Academic/types';
import ApplicantLayout from '@/Modules/Ppdb/Components/ApplicantLayout';
import type { ApplicantDetail } from '@/Modules/Ppdb/types';

type Props = { applicant: ApplicantDetail; relationships: Option[] };

export default function Reregister({ applicant, relationships }: Props) {
    const form = useForm({
        address: applicant.student.address ?? '',
        guardians: applicant.guardians.map((g) => ({
            ...g,
            nik: g.nik ?? '',
            occupation: g.occupation ?? '',
            phone: g.phone ?? '',
            email: g.email ?? '',
        })),
        agreement: false,
    });
    const errors = form.errors as Record<string, string | undefined>;
    const set = (
        i: number,
        key: 'name' | 'phone' | 'occupation' | 'email' | 'nik',
        value: string,
    ) =>
        form.setData(
            'guardians',
            form.data.guardians.map((g, j) =>
                j === i ? { ...g, [key]: value } : g,
            ),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/ppdb/akun/pendaftaran/${applicant.public_id}/daftar-ulang`);
    };

    return (
        <ApplicantLayout>
            <Head title="Daftar ulang" />
            <Button
                variant="ghost"
                size="sm"
                className="-ml-2 self-start"
                asChild
            >
                <Link href="/ppdb/akun/dashboard">
                    <ArrowLeft />
                    Dashboard
                </Link>
            </Button>
            <section className="flex items-start gap-3 rounded-2xl bg-emerald-50 p-4 text-emerald-900">
                <PartyPopper className="mt-0.5 size-5 shrink-0" />
                <div>
                    <h1 className="font-semibold">
                        Selamat, {applicant.name} diterima!
                    </h1>
                    <p className="text-sm">
                        Periksa dan lengkapi data di bawah untuk daftar ulang.
                        Sekolah akan mengonfirmasi dan menerbitkan data siswa.
                    </p>
                </div>
            </section>
            <form
                onSubmit={submit}
                className="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
            >
                <div className="grid gap-2">
                    <Label htmlFor="rr-address">Alamat tempat tinggal</Label>
                    <Textarea
                        id="rr-address"
                        rows={3}
                        maxLength={500}
                        value={form.data.address}
                        onChange={(e) =>
                            form.setData('address', e.target.value)
                        }
                    />
                    <InputError message={errors.address} />
                </div>
                {form.data.guardians.map((g, i) => (
                    <fieldset
                        key={g.relationship}
                        className="space-y-3 rounded-xl border border-slate-200 p-3"
                    >
                        <legend className="text-sm font-semibold text-slate-900">
                            {
                                relationships.find(
                                    (r) => r.value === g.relationship,
                                )?.label
                            }
                            {g.is_primary && ' (kontak utama)'}
                        </legend>
                        <div className="grid gap-3 sm:grid-cols-2">
                            {(
                                [
                                    'name',
                                    'phone',
                                    'occupation',
                                    'email',
                                ] as const
                            ).map((key) => (
                                <div
                                    key={key}
                                    className="grid content-start gap-2"
                                >
                                    <Label htmlFor={`rr-${i}-${key}`}>
                                        {
                                            {
                                                name: 'Nama',
                                                phone: 'Telepon',
                                                occupation: 'Pekerjaan',
                                                email: 'Email',
                                            }[key]
                                        }
                                    </Label>
                                    <Input
                                        id={`rr-${i}-${key}`}
                                        value={g[key]}
                                        onChange={(e) =>
                                            set(i, key, e.target.value)
                                        }
                                    />
                                    <InputError
                                        message={
                                            errors[`guardians.${i}.${key}`]
                                        }
                                    />
                                </div>
                            ))}
                        </div>
                    </fieldset>
                ))}
                <label className="flex items-start gap-2 text-sm text-slate-700">
                    <Checkbox
                        checked={form.data.agreement}
                        onCheckedChange={(c) =>
                            form.setData('agreement', c === true)
                        }
                        className="mt-0.5"
                    />
                    Saya menyatakan data benar dan bersedia mengikuti ketentuan
                    sekolah.
                </label>
                <InputError message={errors.agreement} />
                <Button
                    type="submit"
                    className="w-full"
                    disabled={form.processing || !form.data.agreement}
                >
                    {form.processing && <Spinner />}
                    Kirim daftar ulang
                </Button>
            </form>
        </ApplicantLayout>
    );
}
