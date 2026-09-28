export type AnnouncementStatusValue = 'DRAF' | 'TERJADWAL' | 'TERBIT';

export type AnnouncementAttachment = {
    public_id: string;
    name: string;
    mime_type: string;
    is_image: boolean;
    size: number;
    url: string;
};

export type AnnouncementRow = {
    public_id: string;
    title: string;
    body: string;
    pinned: boolean;
    status: { value: AnnouncementStatusValue; label: string };
    publish_at: string | null;
    audience: { value: 'SEMUA' | 'PERAN' | 'ROMBEL'; label: string };
    roles: string[];
    classrooms: { public_id: string; name: string }[];
    attachments: AnnouncementAttachment[];
    creator: string | null;
    created_at: string;
};
