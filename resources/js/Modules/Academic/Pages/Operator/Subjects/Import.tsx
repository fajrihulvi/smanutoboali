import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ImportWorkspace } from '@/Modules/People/Components/ImportWorkspace';
import type { ImportRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    imports: Paginated<ImportRow>;
};

const BASE = '/operator/master-data/mapel';

export default function SubjectImport({ imports }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: BASE },
                { title: 'Mata Pelajaran', href: BASE },
                { title: 'Impor Excel', href: `${BASE}/impor` },
            ]}
        >
            <Head title="Impor Mata Pelajaran" />

            <ImportWorkspace
                base={BASE}
                imports={imports}
                title="Impor mata pelajaran"
                description="Unggah banyak mata pelajaran sekaligus dari Excel. File diproses di latar belakang; baris yang gagal dilaporkan."
                backLabel="Kembali ke data mapel"
                instructions={
                    <>
                        <li>Unduh template dan isi satu mapel per baris.</li>
                        <li>
                            Wajib: <b>kode</b> (huruf, angka, titik, atau tanda
                            hubung; maks. 20 karakter) dan <b>nama</b>.
                        </li>
                        <li>
                            <b>kelompok</b>: Umum, Pilihan, Muatan Lokal, atau
                            Kepesantrenan.
                        </li>
                        <li>
                            <b>aktif</b>: Ya atau Tidak. Kosong berarti aktif.
                        </li>
                        <li>
                            Kode yang sudah terdaftar dilewati dan dilaporkan.
                        </li>
                    </>
                }
            />
        </AppLayout>
    );
}
