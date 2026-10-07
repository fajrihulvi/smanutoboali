import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ImportWorkspace } from '@/Modules/People/Components/ImportWorkspace';
import type { ImportRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    imports: Paginated<ImportRow>;
};

const BASE = '/operator/master-data/siswa';

export default function StudentImport({ imports }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Siswa', href: BASE },
                { title: 'Impor Excel', href: `${BASE}/impor` },
            ]}
        >
            <Head title="Impor Siswa" />

            <ImportWorkspace
                base={BASE}
                imports={imports}
                title="Impor siswa & orang tua"
                description="Unggah banyak siswa sekaligus dari Excel. File diproses di latar belakang; baris yang gagal dilaporkan."
                backLabel="Kembali ke data siswa"
                instructions={
                    <>
                        <li>Unduh template dan isi satu siswa per baris.</li>
                        <li>
                            Wajib: <b>nis</b>, <b>nama</b>, <b>jenis_kelamin</b>{' '}
                            (L/P).
                        </li>
                        <li>Tanggal: YYYY-MM-DD atau DD/MM/YYYY.</li>
                        <li>
                            Orang tua (ayah/ibu/wali) opsional; data yang sama
                            dipakai ulang untuk kakak-adik.
                        </li>
                        <li>
                            NIS yang sudah terdaftar dilewati dan dilaporkan.
                        </li>
                    </>
                }
            />
        </AppLayout>
    );
}
