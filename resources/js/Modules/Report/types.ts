export type ReportField = {
    name: string;
    label: string;
    type: 'date' | 'select';
    options?: { value: string; label: string }[];
    required: boolean;
    default: string | null;
    placeholder?: string;
};

export type ReportPreview = {
    title: string;
    meta: string[];
    columns: string[];
    rows: string[][];
    footer: string[] | null;
    numeric: number[];
    total: number;
};

export type ExportRow = {
    public_id: string;
    title: string;
    format: string;
    status: {
        value: 'PENDING' | 'PROCESSING' | 'DONE' | 'FAILED';
        label: string;
    };
    rows: number | null;
    created_at: string;
    expires_at: string;
    download_url: string | null;
};
