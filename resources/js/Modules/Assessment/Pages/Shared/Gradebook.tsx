import { Head, router } from '@inertiajs/react';
import {
    CheckCircle2,
    CircleAlert,
    Download,
    FileText,
    Lock,
    LockOpen,
    MoreVertical,
    Pencil,
    Plus,
    Send,
    Sparkles,
    Trash2,
    Upload,
    Users,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Spinner } from '@/Components/ui/spinner';
import { useConfirmDelete } from '@/hooks/use-confirm-delete';
import AppLayout from '@/Layouts/AppLayout';
import { toastFirstError } from '@/lib/toast-errors';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { ComponentFormDialog } from '@/Modules/Assessment/Components/ComponentFormDialog';
import { DescriptionDialog } from '@/Modules/Assessment/Components/DescriptionDialog';
import type { DescriptionTarget } from '@/Modules/Assessment/Components/DescriptionDialog';
import { GradeGrid } from '@/Modules/Assessment/Components/GradeGrid';
import type { SaveState } from '@/Modules/Assessment/Components/GradeGrid';
import { ImportDialog } from '@/Modules/Assessment/Components/ImportDialog';
import { TemplatesDialog } from '@/Modules/Assessment/Components/TemplatesDialog';
import type {
    GradeComponentRow,
    GradebookProps,
} from '@/Modules/Assessment/types';

type Props = GradebookProps & {
    semesters: Option[];
    types: Option[];
    base: string;
};

export default function Gradebook({
    assignment,
    semester,
    components,
    students,
    totalWeight,
    kkm,
    templates,
    readOnly,
    semesters,
    types,
    base,
}: Props) {
    const url = `${base}/${assignment.public_id}`;
    const q = `?semester=${semester.public_id}`;
    const [componentDialog, setComponentDialog] = useState<{
        open: boolean;
        component: GradeComponentRow | null;
    }>({
        open: false,
        component: null,
    });
    const [description, setDescription] = useState<{
        open: boolean;
        target: DescriptionTarget | null;
    }>({ open: false, target: null });
    // Tanda tangan data server: berubah setelah impor/isi template/aksi komponen → grid & override direset.
    const dataKey = [
        components
            .map((c) => `${c.public_id}${c.published_at ? 'p' : ''}${c.weight}`)
            .join('|'),
        students
            .map(
                (s) =>
                    `${s.public_id}:${Object.values(s.scores).join(',')}:${s.description ?? ''}`,
            )
            .join('|'),
    ].join('#');
    const [overrides, setOverrides] = useState<{
        key: string;
        map: Record<string, string | null>;
    }>({ key: dataKey, map: {} });
    const descriptions: Record<string, string | null> = {
        ...Object.fromEntries(
            students.map((student) => [student.public_id, student.description]),
        ),
        ...(overrides.key === dataKey ? overrides.map : {}),
    };
    const [templatesOpen, setTemplatesOpen] = useState(false);
    const [importOpen, setImportOpen] = useState(false);
    const [saveState, setSaveState] = useState<SaveState>({
        saving: 0,
        failed: 0,
        lastError: null,
    });
    const onSaveStateChange = useCallback(
        (state: SaveState) => setSaveState(state),
        [],
    );
    const { confirmDelete, dialog: deleteDialog } = useConfirmDelete();

    const act = (method: 'post' | 'delete', path: string) =>
        router[method](
            path,
            {},
            {
                preserveScroll: true,
                onError: (errors) => toastFirstError(errors),
            },
        );

    const weightOk = totalWeight === 100;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Nilai', href: base },
                {
                    title: `${assignment.subject} ${assignment.classroom}`,
                    href: '#',
                },
            ]}
        >
            <Head
                title={`Nilai ${assignment.subject} ${assignment.classroom}`}
            />

            <PageHeader
                title={`${assignment.subject} · ${assignment.classroom}`}
                description={`Pengampu ${assignment.teacher} · KKM ${kkm}${readOnly ? ' · hanya lihat' : ''}`}
                actions={
                    !readOnly && (
                        <>
                            <Button variant="outline" asChild>
                                <a href={`${url}/ekspor${q}`}>
                                    <Download />
                                    Ekspor Excel
                                </a>
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setImportOpen(true)}
                                disabled={components.length === 0}
                            >
                                <Upload />
                                Impor
                            </Button>
                        </>
                    )
                }
            />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <NativeSelect
                    aria-label="Semester"
                    className="w-44"
                    value={semester.public_id}
                    onChange={(event) =>
                        router.get(url, { semester: event.target.value })
                    }
                >
                    {semesters.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </NativeSelect>
                <StatusBadge variant={weightOk ? 'success' : 'warning'}>
                    Total bobot {totalWeight}%
                    {weightOk ? '' : ' — harus 100% agar nilai akhir dihitung'}
                </StatusBadge>
                {!readOnly && (
                    <p
                        className="ml-auto flex items-center gap-1.5 text-sm text-slate-500"
                        aria-live="polite"
                    >
                        {saveState.saving > 0 ? (
                            <>
                                <Spinner className="size-4" /> Menyimpan…
                            </>
                        ) : saveState.failed > 0 ? (
                            <span className="flex items-center gap-1 text-rose-600">
                                <CircleAlert className="size-4" />{' '}
                                {saveState.lastError ??
                                    'Ada nilai yang gagal disimpan.'}
                            </span>
                        ) : (
                            <>
                                <CheckCircle2 className="size-4 text-emerald-500" />{' '}
                                Semua perubahan tersimpan
                            </>
                        )}
                    </p>
                )}
            </div>

            {!readOnly && (
                <div className="mb-4 flex flex-wrap gap-2">
                    <Button
                        onClick={() =>
                            setComponentDialog({ open: true, component: null })
                        }
                    >
                        <Plus />
                        Komponen
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() => setTemplatesOpen(true)}
                    >
                        <FileText />
                        Template deskripsi
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() =>
                            router.post(
                                `${url}/deskripsi/template${q}`,
                                { overwrite: false },
                                { preserveScroll: true },
                            )
                        }
                        disabled={students.length === 0}
                    >
                        <Sparkles />
                        Isi deskripsi kosong
                    </Button>
                </div>
            )}

            {students.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Users}
                        title="Rombel belum punya siswa aktif"
                    />
                </div>
            ) : components.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={FileText}
                        title="Belum ada komponen nilai"
                        description={
                            readOnly
                                ? 'Guru belum membuat komponen nilai.'
                                : 'Tambahkan komponen (mis. Tugas 20%, UH 30%, PTS 20%, PAS 30%).'
                        }
                    />
                </div>
            ) : (
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <GradeGrid
                        key={dataKey}
                        components={components}
                        students={students}
                        kkm={kkm}
                        readOnly={readOnly}
                        descriptions={descriptions}
                        onSaveStateChange={onSaveStateChange}
                        onEditDescription={(student, result) =>
                            setDescription({
                                open: true,
                                target: {
                                    student,
                                    description:
                                        descriptions[student.public_id] ?? null,
                                    suggested: result?.suggested ?? null,
                                },
                            })
                        }
                        renderComponentHeader={(component) => (
                            <div className="flex items-center justify-center gap-1">
                                <div>
                                    <p className="flex items-center justify-center gap-1 whitespace-nowrap text-slate-900">
                                        {component.published_at && (
                                            <Lock
                                                className="size-3 text-emerald-600"
                                                aria-label="Dipublikasikan"
                                            />
                                        )}
                                        {component.name}
                                    </p>
                                    <p className="font-normal text-slate-500">
                                        {component.weight}%
                                    </p>
                                </div>
                                {!readOnly && (
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-7"
                                                aria-label={`Aksi ${component.name}`}
                                            >
                                                <MoreVertical className="size-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            {component.published_at ? (
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        act(
                                                            'delete',
                                                            `${base}/komponen/${component.public_id}/publikasi`,
                                                        )
                                                    }
                                                >
                                                    <LockOpen />
                                                    Batalkan publikasi
                                                </DropdownMenuItem>
                                            ) : (
                                                <>
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            act(
                                                                'post',
                                                                `${base}/komponen/${component.public_id}/publikasi`,
                                                            )
                                                        }
                                                    >
                                                        <Send />
                                                        Publikasikan ke orang
                                                        tua
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            setComponentDialog({
                                                                open: true,
                                                                component,
                                                            })
                                                        }
                                                    >
                                                        <Pencil />
                                                        Ubah
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onSelect={() =>
                                                            confirmDelete({
                                                                url: `${base}/komponen/${component.public_id}`,
                                                                title: `Hapus komponen ${component.name}?`,
                                                                description:
                                                                    'Semua nilai di komponen ini ikut terhapus.',
                                                            })
                                                        }
                                                    >
                                                        <Trash2 />
                                                        Hapus
                                                    </DropdownMenuItem>
                                                </>
                                            )}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                )}
                            </div>
                        )}
                    />
                </section>
            )}

            {!readOnly && (
                <>
                    <ComponentFormDialog
                        open={componentDialog.open}
                        onOpenChange={(open) =>
                            setComponentDialog((current) => ({
                                ...current,
                                open,
                            }))
                        }
                        component={componentDialog.component}
                        assignment={assignment.public_id}
                        semester={semester.public_id}
                        types={types}
                        remainingWeight={
                            100 -
                            totalWeight +
                            (componentDialog.component?.weight ?? 0)
                        }
                    />
                    <TemplatesDialog
                        open={templatesOpen}
                        onOpenChange={setTemplatesOpen}
                        templates={templates}
                        url={`${url}/template`}
                    />
                    <ImportDialog
                        open={importOpen}
                        onOpenChange={setImportOpen}
                        url={`${url}/impor${q}`}
                    />
                </>
            )}
            <DescriptionDialog
                open={description.open}
                onOpenChange={(open) =>
                    setDescription((current) => ({ ...current, open }))
                }
                target={description.target}
                url={`${url}/deskripsi${q}`}
                readOnly={readOnly}
                onSaved={(student, text) =>
                    setOverrides((current) => ({
                        key: dataKey,
                        map: {
                            ...(current.key === dataKey ? current.map : {}),
                            [student]: text,
                        },
                    }))
                }
            />
            {deleteDialog}
        </AppLayout>
    );
}
