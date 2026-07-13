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

// Si el servidor publicó una nueva build (nuevos hashes de archivo) mientras el
// usuario tenía la pestaña abierta, el import() dinámico de una página que aún
// no se había cargado falla con "Failed to fetch dynamically imported module".
// En ese caso, forzamos una recarga completa (una sola vez, para no hacer un
// loop) en vez de dejar la app rota.
const recargarPorBuildDesactualizada = (error) => {
    const esErrorDeImportacion =
        /Failed to fetch dynamically imported module|error loading dynamically imported module|Importing a module script failed/i.test(
            error?.message ?? '',
        );

    if (!esErrorDeImportacion) {
        throw error;
    }

    const yaRecargado = sessionStorage.getItem('recarga-por-build-nueva');
    if (yaRecargado) {
        throw error;
    }

    sessionStorage.setItem('recarga-por-build-nueva', '1');
    window.location.reload();

    // Nunca resuelve: la recarga de la página interrumpe la ejecución.
    return new Promise(() => {});
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ).catch(recargarPorBuildDesactualizada),
    setup({ el, App, props }) {
        // Una página se resolvió con éxito: si más adelante hay otro despliegue,
        // debe poder volver a recargar automáticamente.
        sessionStorage.removeItem('recarga-por-build-nueva');

        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
