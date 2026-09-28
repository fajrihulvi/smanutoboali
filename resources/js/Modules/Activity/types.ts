export type ActivityRow = {
    public_id: string;
    title: string;
    type: { value: 'HARIAN' | 'KELAS' | 'SEKOLAH'; label: string };
    date: string;
    start_time: string | null;
    end_time: string | null;
    location: string | null;
    description: string | null;
    status: { value: 'DRAF' | 'TERBIT'; label: string };
    classrooms: { public_id: string; name: string }[];
    students: { public_id: string; name: string }[];
    photos: { public_id: string; name: string; url: string }[];
    creator: string | null;
    can: { update: boolean };
};
