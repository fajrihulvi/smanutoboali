import { useState } from 'react';
import { Lightbox } from '@/Components/shared/Lightbox';
import { PortfolioCard } from '@/Modules/Portfolio/Components/PortfolioCard';
import type { PortfolioRow } from '@/Modules/Portfolio/types';

type Props = {
    portfolios: PortfolioRow[];
    /** Prefix URL ubah, mis. "/guru/portofolio" → "/guru/portofolio/{id}/ubah". */
    editBase?: string;
    showVisibility?: boolean;
};

/**
 * Grid kartu portofolio + satu lightbox untuk semua foto per portofolio.
 */
export function PortfolioGrid({ portfolios, editBase, showVisibility }: Props) {
    const [viewer, setViewer] = useState<{
        portfolio: PortfolioRow;
        index: number;
    } | null>(null);
    const images = viewer
        ? viewer.portfolio.files
              .filter((file) => file.is_image)
              .map((file) => ({ url: file.url, name: file.name }))
        : [];

    return (
        <>
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {portfolios.map((portfolio) => (
                    <PortfolioCard
                        key={portfolio.public_id}
                        portfolio={portfolio}
                        showVisibility={showVisibility}
                        editHref={
                            editBase
                                ? `${editBase}/${portfolio.public_id}/ubah`
                                : undefined
                        }
                        onOpenImage={(index) => setViewer({ portfolio, index })}
                    />
                ))}
            </div>
            <Lightbox
                images={images}
                index={viewer?.index ?? null}
                title={viewer?.portfolio.title}
                onIndexChange={(index) =>
                    setViewer((current) =>
                        current && index !== null
                            ? { ...current, index }
                            : null,
                    )
                }
            />
        </>
    );
}
