import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

// Input de búsqueda con debounce que actualiza el filtro `campo` en la URL,
// preservando el resto de `filtros` ya aplicados.
export default function useBusqueda(rutaNombre, filtros, campo = 'q', delay = 300) {
    const [valor, setValor] = useState(filtros[campo] ?? '');
    const primerRender = useRef(true);

    useEffect(() => {
        if (primerRender.current) {
            primerRender.current = false;
            return;
        }
        const id = setTimeout(() => {
            router.get(
                route(rutaNombre),
                { ...filtros, [campo]: valor || undefined },
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, delay);
        return () => clearTimeout(id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [valor]);

    return [valor, setValor];
}
