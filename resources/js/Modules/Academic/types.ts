export type Option = { value: string; label: string };

export type YearOption = Option & { is_active: boolean };

export type GradeOption = { value: number; label: string };

export type ClassroomRow = {
    public_id: string;
    name: string;
    grade: number;
    grade_label: string;
    capacity: number | null;
    students_count?: number;
    academic_year?: { public_id: string; name: string; is_active: boolean };
    homeroom_teacher?: { public_id: string; name: string } | null;
};

export type ClassStudent = {
    public_id: string;
    nis: string;
    name: string;
    gender: string;
};

export type SubjectOption = { public_id: string; code: string; name: string };

export type SubjectRow = SubjectOption & {
    group: Option;
    is_active: boolean;
    assignments_count?: number;
};
