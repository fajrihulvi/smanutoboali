import { createInertiaApp } from '@inertiajs/react';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { TooltipProvider } from '@/Components/ui/tooltip';
import '../css/app.css';
import { resolvePage } from '@/lib/resolve-page';

const appName = import.meta.env.VITE_APP_NAME || 'Skoolia';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePage(name),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <TooltipProvider delayDuration={0}>
                    <App {...props} />
                </TooltipProvider>
            </StrictMode>,
        );
    },
    progress: {
        color: 'var(--color-brand-500)',
    },
});
