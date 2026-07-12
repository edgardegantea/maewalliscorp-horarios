import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { aplicarTema, obtenerTema } from './theme';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Si el usuario dejó el tema en "sistema", sigue los cambios de preferencia
// del sistema operativo mientras la app está abierta.
window
    .matchMedia('(prefers-color-scheme: dark)')
    .addEventListener('change', () => {
        if (obtenerTema() === 'sistema') {
            aplicarTema('sistema');
        }
    });

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
