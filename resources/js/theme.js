const CLAVE = 'tema';

export function obtenerTema() {
    return localStorage.getItem(CLAVE) ?? 'sistema';
}

export function esOscuroActivo() {
    const tema = obtenerTema();

    if (tema === 'oscuro') return true;
    if (tema === 'claro') return false;

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function aplicarTema(tema) {
    if (tema === 'sistema') {
        localStorage.removeItem(CLAVE);
    } else {
        localStorage.setItem(CLAVE, tema);
    }

    document.documentElement.classList.toggle('dark', esOscuroActivo());
}
