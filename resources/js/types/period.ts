export type ActivePeriod = {
    academic_year: { public_id: string; name: string };
    semester: {
        public_id: string;
        type: 'GANJIL' | 'GENAP';
        label: string;
    } | null;
};
