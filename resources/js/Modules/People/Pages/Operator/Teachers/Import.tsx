import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ImportWorkspace } from '@/Modules/People/Components/ImportWorkspace';
import type { ImportRow } from '@/Modules/People/types';
import type { Paginated } from '@/types';

type Props = {
    imports: Paginated<ImportRow>;
};

const BASE = '/operator/master-data/guru';

export default function TeacherImport({ imports }: Props) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Master Data', href: BASE },
                { title: 'Guru', href: BASE },
                { title: 'Impor Excel', href: `${BASE}/impor` },
            ]}
        >
            <Head title="Impor Guru" />

            <ImportWorkspace
                base={BASE}
                imports={imports}
                title="Impor guru"
                description="Unggah banyak guru sekaligus dari Excel. File diproses di latar belakang; baris yang gagal dilaporkan."
                backLabel="Kembali ke data guru"
                instructions={
                    <>
                        <li>Unduh template dan isi satu guru per baris.</li>
                        <li>
                            Wajib: <b>nama</b>, <b>jenis_kelamin</b> (L/P),{' '}
                            <b>status_kepegawaian</b> (PNS, PPPK, GTY, GTT, atau
                            HONORER).
                        </li>
                        <li>
                            <b>nip</b> 18 digit dan <b>nuptk</b> 16 digit,
                            opsional. Biarkan kolomnya berformat Teks agar digit
                            tidak berubah.
                        </li>
                        <li>
                            NIP/NUPTK yang sudah terdaftar dilewati dan
                            dilaporkan.
                        </li>
                        <li>
                            Akun login dibuat terpisah lewat tombol <b>Ubah</b>{' '}
                            pada tiap guru.
                        </li>
                    </>
                }
            />
        </AppLayout>
    );
}
