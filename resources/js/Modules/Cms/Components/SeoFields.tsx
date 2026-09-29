import InputError from '@/Components/input-error';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { FormCard } from '@/Modules/Cms/Components/FormCard';

type Props = {
    slug: string;
    seoTitle: string;
    seoDescription: string;
    onChange: (
        key: 'slug' | 'seo_title' | 'seo_description',
        value: string,
    ) => void;
    errors: { slug?: string; seo_title?: string; seo_description?: string };
    /** Awalan URL publik, mis. "/berita/". */
    urlPrefix: string;
    slugLocked?: boolean;
};

function Counter({ value, max }: { value: string; max: number }) {
    return (
        <span className="text-xs text-slate-400 tabular-nums">
            {value.length}/{max}
        </span>
    );
}

/**
 * Slug & metadata SEO. Slug kosong dibuat otomatis dari judul.
 */
export function SeoFields({
    slug,
    seoTitle,
    seoDescription,
    onChange,
    errors,
    urlPrefix,
    slugLocked,
}: Props) {
    return (
        <FormCard title="URL & SEO">
            <div className="grid gap-2">
                <Label htmlFor="content-slug">Slug</Label>
                <div className="flex min-w-0 items-center rounded-md border border-input bg-slate-50 text-sm shadow-xs">
                    <span className="shrink-0 pl-3 text-slate-500">
                        {urlPrefix}
                    </span>
                    <Input
                        id="content-slug"
                        value={slug}
                        maxLength={160}
                        disabled={slugLocked}
                        onChange={(e) =>
                            onChange('slug', e.target.value.toLowerCase())
                        }
                        placeholder="otomatis-dari-judul"
                        className="min-w-0 border-0 bg-transparent pl-0.5 shadow-none focus-visible:ring-0"
                        aria-invalid={!!errors.slug}
                    />
                </div>
                <p className="text-xs text-slate-500">
                    {slugLocked
                        ? 'Slug halaman sistem tidak bisa diubah.'
                        : 'Huruf kecil, angka, dan tanda hubung. Kosongkan untuk dibuat dari judul.'}
                </p>
                <InputError message={errors.slug} />
            </div>
            <div className="grid gap-2">
                <div className="flex items-center justify-between">
                    <Label htmlFor="content-seo-title">Judul SEO</Label>
                    <Counter value={seoTitle} max={70} />
                </div>
                <Input
                    id="content-seo-title"
                    value={seoTitle}
                    maxLength={70}
                    onChange={(e) => onChange('seo_title', e.target.value)}
                    placeholder="Kosongkan untuk memakai judul"
                    aria-invalid={!!errors.seo_title}
                />
                <InputError message={errors.seo_title} />
            </div>
            <div className="grid gap-2">
                <div className="flex items-center justify-between">
                    <Label htmlFor="content-seo-description">
                        Deskripsi SEO
                    </Label>
                    <Counter value={seoDescription} max={160} />
                </div>
                <Textarea
                    id="content-seo-description"
                    rows={3}
                    value={seoDescription}
                    maxLength={160}
                    onChange={(e) =>
                        onChange('seo_description', e.target.value)
                    }
                    placeholder="Ringkasan singkat untuk hasil pencarian Google"
                    aria-invalid={!!errors.seo_description}
                />
                <InputError message={errors.seo_description} />
            </div>
        </FormCard>
    );
}
