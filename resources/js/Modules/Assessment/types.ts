export type PredicateValue = 'A' | 'B' | 'C' | 'D';

export type GradeComponentRow = {
    public_id: string;
    type: string;
    name: string;
    weight: number;
    published_at: string | null;
};

export type GradebookStudent = {
    public_id: string;
    nis: string;
    name: string;
    scores: Record<string, number | null>;
    final: number | null;
    predicate: PredicateValue | null;
    description: string | null;
    suggested: string | null;
};

export type GradebookProps = {
    assignment: {
        public_id: string;
        subject: string;
        classroom: string;
        teacher: string;
    };
    semester: { public_id: string; label: string };
    components: GradeComponentRow[];
    students: GradebookStudent[];
    totalWeight: number;
    kkm: number;
    templates: Record<PredicateValue, string>;
    readOnly: boolean;
};

export type CellResult = {
    final: number | null;
    predicate: PredicateValue | null;
    suggested: string | null;
};
