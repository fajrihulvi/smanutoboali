export type MediaFile = {
    public_id: string;
    name: string;
    mime_type: string;
    is_image: boolean;
    size: number;
    url: string;
};

export type PortfolioRow = {
    public_id: string;
    title: string;
    description: string | null;
    date: string;
    category: { code: string; name: string };
    visibility: { value: 'DRAFT' | 'PUBLISHED'; label: string };
    students: { public_id: string; name: string }[];
    students_count: number;
    files: MediaFile[];
    creator: string | null;
    can: { update: boolean };
};
