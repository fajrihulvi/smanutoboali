export type Option = { value: string; label: string };

export type GuestVisitRow = {
    public_id: string;
    full_name: string;
    institution: string;
    whatsapp: string;
    whatsapp_url: string;
    category: Option;
    purpose: string;
    host: Option;
    notes: string | null;
    visited_at: string;
    source: Option;
    photo_url: string | null;
    photo_thumb_url: string | null;
    can: { update: boolean; delete: boolean };
};
