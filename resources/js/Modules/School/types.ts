export type SemesterRow = {
    public_id: string;
    type: 'GANJIL' | 'GENAP';
    label: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
};

export type AcademicYearRow = {
    public_id: string;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    can_delete: boolean;
    semesters: SemesterRow[];
};
