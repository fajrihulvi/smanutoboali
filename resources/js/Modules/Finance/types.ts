export type InvoiceStatusValue =
    'UNPAID' | 'PENDING' | 'PAID' | 'FAILED' | 'CANCELLED';

export type PaymentRow = {
    public_id: string;
    method: { value: string; label: string };
    status: { value: 'PENDING' | 'CONFIRMED' | 'REJECTED'; label: string };
    amount: number;
    paid_on: string;
    receipt_number: string | null;
    note: string | null;
    rejection_reason: string | null;
    submitted_by: string | null;
    verified_by: string | null;
    verified_at: string | null;
    created_at: string;
    proof: { url: string; name: string; is_image: boolean } | null;
    invoice?: {
        public_id: string;
        number: string;
        title: string;
        total: number;
        remaining: number;
        student: { public_id: string; name: string; nis: string };
    };
};

export type InvoiceRow = {
    public_id: string;
    number: string;
    title: string;
    fee_type: string;
    period: string;
    period_label: string;
    student: { public_id: string; name: string; nis: string };
    classroom: string | null;
    due_date: string;
    total: number;
    paid_amount: number;
    remaining: number;
    status: { value: InvoiceStatusValue; label: string };
    overdue: boolean;
    created_at: string;
};

export type InvoiceDetail = InvoiceRow & {
    notes: string | null;
    paid_at: string | null;
    cancelled_at: string | null;
    items: {
        type: 'BIAYA' | 'POTONGAN';
        description: string;
        amount: number;
    }[];
    payments: PaymentRow[];
    can: { pay: boolean; manage: boolean };
};

export type FeeTypeRow = {
    public_id: string;
    name: string;
    default_amount: number;
    frequency: { value: 'BULANAN' | 'SEKALI'; label: string };
    is_active: boolean;
    description: string | null;
    invoices_count: number;
};

export type DiscountRow = {
    public_id: string;
    student: { public_id: string; name: string; nis: string };
    fee_type: { public_id: string; name: string } | null;
    kind: { value: 'PERSEN' | 'NOMINAL'; label: string };
    value: number;
    label: string;
    reason: string;
    is_active: boolean;
};

export type BatchRow = {
    public_id: string;
    title: string;
    scope: string;
    status: { value: 'QUEUED' | 'RUNNING' | 'DONE' | 'FAILED'; label: string };
    created_count: number;
    skipped_count: number;
    total_amount: number;
    due_date: string;
    error: string | null;
    creator: string | null;
    created_at: string;
};

export type FinanceSummary = {
    outstanding: number;
    outstanding_invoices: number;
    overdue: number;
    received_this_month: number;
    pending_verification: number;
};
