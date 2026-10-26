/**
 * Gaya isi rich text (editor CMS & pratinjau) lewat varian Tailwind,
 * tanpa aturan CSS global.
 */
export const PROSE_CLASS = [
    'text-[15px] leading-7 text-slate-800',
    '[&_p]:my-3 [&_p:first-child]:mt-0',
    '[&_h2]:mt-6 [&_h2]:mb-2 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-slate-900',
    '[&_h3]:mt-5 [&_h3]:mb-2 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-slate-900',
    '[&_h4]:mt-4 [&_h4]:mb-1 [&_h4]:font-semibold [&_h4]:text-slate-900',
    '[&_ul]:my-3 [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:my-3 [&_ol]:list-decimal [&_ol]:pl-6 [&_li_p]:my-1',
    '[&_blockquote]:my-4 [&_blockquote]:border-l-4 [&_blockquote]:border-brand-200 [&_blockquote]:pl-4 [&_blockquote]:text-slate-600',
    '[&_a]:text-brand-700 [&_a]:underline',
    '[&_hr]:my-6 [&_hr]:border-slate-200',
    '[&_img]:my-4 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-lg',
    '[&_img.ProseMirror-selectednode]:ring-2 [&_img.ProseMirror-selectednode]:ring-brand-400',
    // Tabel memenuhi lebar editor; bergulir horizontal bila kolomnya terlalu banyak.
    '[&_.tableWrapper]:my-4 [&_.tableWrapper]:overflow-x-auto',
    '[&_table]:w-full [&_table]:border-collapse [&_table]:text-sm',
    '[&_th]:border [&_th]:border-slate-300 [&_th]:bg-slate-50 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left [&_th]:align-top [&_th]:font-semibold [&_th]:text-slate-900',
    '[&_td]:border [&_td]:border-slate-300 [&_td]:px-3 [&_td]:py-2 [&_td]:align-top',
    '[&_td_p]:my-0 [&_th_p]:my-0 [&_td]:min-w-24 [&_th]:min-w-24',
    '[&_.selectedCell]:bg-brand-50',
].join(' ');
