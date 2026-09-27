import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    ClipboardCheck,
    GraduationCap,
    Plus,
    SearchX,
    Trash2,
    Users,
    Wallet,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { DataTable } from '@/Components/shared/DataTable';
import type { DataTableColumn } from '@/Components/shared/DataTable';
import { EmptyState } from '@/Components/shared/EmptyState';
import { FileUploader } from '@/Components/shared/FileUploader';
import { FilterBar } from '@/Components/shared/FilterBar';
import { PageHeader } from '@/Components/shared/PageHeader';
import { CardSkeleton, TableSkeleton } from '@/Components/shared/Skeletons';
import { StatCard } from '@/Components/shared/StatCard';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useFilters } from '@/hooks/use-filters';
import AppLayout from '@/Layouts/AppLayout';
import { formatCurrency, formatDate, formatRelative } from '@/lib/format';
import type { Paginated, StatusVariant } from '@/types';

type Student = {
    id: string;
    name: string;
    class: string;
    status: 'HADIR' | 'SAKIT' | 'IZIN' | 'ALPA' | 'TERLAMBAT';
    attendance: number;
};

type Props = {
    students: Paginated<Student>;
    filters: { search?: string; status?: string };
};

const statusVariant: Record<Student['status'], StatusVariant> = {
    HADIR: 'success',
    SAKIT: 'info',
    IZIN: 'warning',
    ALPA: 'danger',
    TERLAMBAT: 'warning',
};

const statusLabel: Record<Student['status'], string> = {
    HADIR: 'Hadir',
    SAKIT: 'Sakit',
    IZIN: 'Izin',
    ALPA: 'Alpa',
    TERLAMBAT: 'Terlambat',
};

// Contoh statis untuk demo format tanggal (bukan dihitung saat render).
const sampleDate = '2026-09-27T07:12:00+07:00';

const brandScale = [
    '50',
    '100',
    '200',
    '300',
    '400',
    '500',
    '600',
    '700',
    '800',
    '900',
    '950',
] as const;

const brandSwatch: Record<(typeof brandScale)[number], string> = {
    '50': 'bg-brand-50',
    '100': 'bg-brand-100',
    '200': 'bg-brand-200',
    '300': 'bg-brand-300',
    '400': 'bg-brand-400',
    '500': 'bg-brand-500',
    '600': 'bg-brand-600',
    '700': 'bg-brand-700',
    '800': 'bg-brand-800',
    '900': 'bg-brand-900',
    '950': 'bg-brand-950',
};

const columns: DataTableColumn<Student>[] = [
    {
        accessorKey: 'name',
        header: 'Nama siswa',
        cell: ({ row }) => (
            <div>
                <p className="font-medium text-slate-900">
                    {row.original.name}
                </p>
                <p className="text-xs text-slate-500">{row.original.id}</p>
            </div>
        ),
    },
    { accessorKey: 'class', header: 'Rombel' },
    {
        accessorKey: 'status',
        header: 'Status hari ini',
        cell: ({ row }) => (
            <StatusBadge variant={statusVariant[row.original.status]} dot>
                {statusLabel[row.original.status]}
            </StatusBadge>
        ),
    },
    {
        accessorKey: 'attendance',
        header: 'Kehadiran',
        meta: { className: 'text-right tabular-nums' },
        cell: ({ row }) => `${row.original.attendance}%`,
    },
];

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-base font-semibold text-slate-900">{title}</h2>
            {description && (
                <p className="mt-0.5 text-xs text-slate-500">{description}</p>
            )}
            <div className="mt-5">{children}</div>
        </section>
    );
}

export default function StyleGuide({ students, filters }: Props) {
    const { values, set, reset, isActive } = useFilters(
        { search: filters.search, status: filters.status },
        { only: ['students', 'filters'] },
    );
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [files, setFiles] = useState<File[]>([]);

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Core', href: '/style-guide' },
                { title: 'Style Guide', href: '/style-guide' },
            ]}
        >
            <Head title="Style Guide" />

            <PageHeader
                title="Style Guide — Elegant Blue"
                description="Semua komponen dasar Skoolia untuk review visual. Hanya tersedia di lingkungan lokal."
                actions={
                    <>
                        <Button variant="outline" asChild>
                            <Link href="/style-guide/orang-tua">
                                Portal Orang Tua
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/style-guide/login?portal=teacher">
                                Halaman Login
                            </Link>
                        </Button>
                    </>
                }
            />

            <Section
                title="Pratinjau menu per role"
                description="Sidebar di kiri menampilkan menu role yang dipilih."
            >
                <div className="flex flex-wrap gap-2">
                    {(
                        [
                            'principal',
                            'teacher',
                            'operator',
                            'guardian',
                        ] as const
                    ).map((role) => (
                        <Button key={role} variant="outline" size="sm" asChild>
                            <Link href={`/style-guide?role=${role}`}>
                                {role}
                            </Link>
                        </Button>
                    ))}
                </div>
            </Section>

            <Section
                title="Palet warna"
                description="Token brand-50…950, brand-accent, dan warna status semantik."
            >
                <div className="grid grid-cols-3 gap-3 sm:grid-cols-6 lg:grid-cols-12">
                    {brandScale.map((step) => (
                        <div key={step} className="space-y-1.5">
                            <div
                                className={`h-14 rounded-xl ring-1 ring-slate-200 ${brandSwatch[step]}`}
                            />
                            <p className="text-xs font-medium text-slate-600">
                                brand-{step}
                            </p>
                        </div>
                    ))}
                    <div className="space-y-1.5">
                        <div className="h-14 rounded-xl bg-brand-accent ring-1 ring-slate-200" />
                        <p className="text-xs font-medium text-slate-600">
                            accent
                        </p>
                    </div>
                </div>
                <div className="mt-6 flex flex-wrap gap-2">
                    {(
                        [
                            'success',
                            'warning',
                            'danger',
                            'info',
                            'neutral',
                            'brand',
                        ] as const
                    ).map((variant) => (
                        <StatusBadge key={variant} variant={variant} dot>
                            {variant}
                        </StatusBadge>
                    ))}
                </div>
            </Section>

            <Section title="Tipografi">
                <div className="space-y-2">
                    <p className="text-2xl font-semibold text-slate-900">
                        Judul halaman — text-2xl semibold
                    </p>
                    <p className="text-base font-semibold text-slate-900">
                        Judul kartu — text-base semibold
                    </p>
                    <p className="text-sm text-slate-600">
                        Isi — text-sm slate-600. Plus Jakarta Sans
                        400/500/600/700.
                    </p>
                    <p className="text-xs text-slate-500">
                        Caption — text-xs slate-500
                    </p>
                    <p className="text-sm text-slate-600">
                        Format: {formatDate(sampleDate)} ·{' '}
                        {formatRelative(sampleDate)} ·{' '}
                        <span className="tabular-nums">
                            {formatCurrency(1250000)}
                        </span>
                    </p>
                </div>
            </Section>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Total siswa"
                    value="482"
                    icon={GraduationCap}
                    trend={{ value: '+12', direction: 'up' }}
                    hint="dari semester lalu"
                />
                <StatCard
                    label="Guru aktif"
                    value="36"
                    icon={Users}
                    hint="2 cuti"
                />
                <StatCard
                    label="Kehadiran hari ini"
                    value="96,4%"
                    icon={ClipboardCheck}
                    trend={{ value: '-1,2%', direction: 'down' }}
                    hint="vs kemarin"
                />
                <StatCard
                    label="Tunggakan"
                    value={formatCurrency(18500000)}
                    icon={Wallet}
                    trend={{
                        value: '-8%',
                        direction: 'down',
                        positive: 'down',
                    }}
                />
            </div>

            <Section title="Tombol & form">
                <div className="flex flex-wrap gap-2">
                    <Button>
                        <Plus /> Primary
                    </Button>
                    <Button variant="secondary">Secondary</Button>
                    <Button variant="outline">Outline</Button>
                    <Button variant="ghost">Ghost</Button>
                    <Button variant="destructive">
                        <Trash2 /> Hapus
                    </Button>
                    <Button disabled>Disabled</Button>
                </div>
                <div className="mt-6 grid max-w-xl gap-4 sm:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="sg-name">Nama lengkap</Label>
                        <Input id="sg-name" placeholder="Contoh: Siti Aminah" />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="sg-email">Email</Label>
                        <Input
                            id="sg-email"
                            aria-invalid
                            defaultValue="bukan-email"
                        />
                        <p className="text-sm text-rose-600">
                            Format email tidak valid.
                        </p>
                    </div>
                </div>
            </Section>

            <DataTable
                columns={columns}
                data={students}
                getRowId={(row) => row.id}
                only={['students', 'filters']}
                empty={{
                    title: 'Siswa tidak ditemukan',
                    description: 'Coba ubah kata kunci atau filter.',
                    icon: SearchX,
                }}
                toolbar={
                    <FilterBar
                        search={values.search}
                        onSearchChange={(value) => set('search', value, true)}
                        searchPlaceholder="Cari nama siswa…"
                        isActive={isActive}
                        onReset={reset}
                    >
                        <Select
                            value={values.status ?? 'all'}
                            onValueChange={(value) =>
                                set(
                                    'status',
                                    value === 'all' ? undefined : value,
                                )
                            }
                        >
                            <SelectTrigger
                                className="w-40"
                                aria-label="Filter status"
                            >
                                <SelectValue placeholder="Semua status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Semua status
                                </SelectItem>
                                {Object.entries(statusLabel).map(
                                    ([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                    </FilterBar>
                }
            />

            <div className="grid gap-6 lg:grid-cols-2">
                <Section title="Empty state">
                    <EmptyState
                        title="Belum ada pengumuman"
                        description="Pengumuman yang Anda buat akan tampil di sini."
                        action={
                            <Button size="sm">
                                <Plus /> Buat pengumuman
                            </Button>
                        }
                    />
                </Section>
                <Section title="Skeleton loader">
                    <div className="space-y-4">
                        <CardSkeleton />
                        <div className="overflow-hidden rounded-2xl border border-slate-200">
                            <TableSkeleton rows={3} />
                        </div>
                    </div>
                </Section>
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Section
                    title="File uploader"
                    description="Drag & drop, preview gambar, validasi tipe & ukuran di sisi klien."
                >
                    <FileUploader
                        value={files}
                        onChange={setFiles}
                        multiple
                        accept={['image/jpeg', 'image/png', 'application/pdf']}
                        maxSizeKb={2048}
                        hint="JPG, PNG, atau PDF · maks 2 MB"
                    />
                </Section>
                <Section title="Toast & dialog konfirmasi">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            onClick={() =>
                                toast.success('Data berhasil disimpan.')
                            }
                        >
                            <CheckCircle2 /> Toast sukses
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => toast.error('Gagal menyimpan data.')}
                        >
                            Toast gagal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setConfirmOpen(true)}
                        >
                            <Trash2 /> Hapus data
                        </Button>
                    </div>
                    <ConfirmDialog
                        open={confirmOpen}
                        onOpenChange={setConfirmOpen}
                        variant="danger"
                        title="Hapus data siswa?"
                        description="Data yang dihapus dapat dipulihkan oleh operator dalam 30 hari."
                        confirmLabel="Ya, hapus"
                        onConfirm={() => {
                            setConfirmOpen(false);
                            toast.success('Data siswa dihapus.');
                        }}
                    />
                </Section>
            </div>
        </AppLayout>
    );
}
