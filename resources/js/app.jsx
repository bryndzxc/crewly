import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Crewly';

createInertiaApp({
    title: (title) => {
        const t = String(title || '').trim();
        if (!t) return appName;
        if (t.toLowerCase() === appName.toLowerCase()) return appName;
        if (t.toLowerCase().endsWith(`- ${appName}`.toLowerCase())) return t;
        return `${t} - ${appName}`;
    },
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
