import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const ESTILOS = {
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20',
    error: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20',
};

export default function FlashBanner() {
    const { success, error } = usePage().props.flash ?? {};
    const mensaje = success ?? error;
    const tipo = success ? 'success' : 'error';

    const [visible, setVisible] = useState(true);

    useEffect(() => {
        setVisible(true);
    }, [mensaje]);

    if (!mensaje || !visible) {
        return null;
    }

    return (
        <div className="px-4 pt-4 sm:px-6 lg:px-8">
            <div
                className={`flex items-start justify-between gap-4 rounded-lg px-4 py-3 text-sm font-medium ring-1 ring-inset ${ESTILOS[tipo]}`}
            >
                <span className="whitespace-pre-line">{mensaje}</span>
                <button
                    onClick={() => setVisible(false)}
                    className="shrink-0 text-current opacity-60 hover:opacity-100"
                    aria-label="Cerrar"
                >
                    ×
                </button>
            </div>
        </div>
    );
}
