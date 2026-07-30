import AuthenticatedLayout from '@/layouts/AuthenticatedLayout';
import client from '@/api/client';
import { useEffect, useState } from 'react';

export default function Dashboard() {
    const [alertas, setAlertas] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        client
            .get('/dashboard')
            .then(({ data }) => setAlertas(data.alertas))
            .finally(() => setLoading(false));
    }, []);

    return (
        <AuthenticatedLayout>
            <h1 className="text-xl font-semibold text-slate-900 dark:text-white">Dashboard</h1>

            {loading && <p className="mt-4 text-sm text-slate-500">Cargando...</p>}

            {!loading && !alertas && (
                <p className="mt-4 text-sm text-slate-500">No hay alertas para mostrar.</p>
            )}

            {alertas && (
                <div className="mt-6 grid gap-6 sm:grid-cols-2">
                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="text-sm font-semibold text-slate-900 dark:text-white">
                            Grupos sin clases ({alertas.periodo})
                        </h2>
                        <ul className="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                            {alertas.grupos_sin_clases.map((g) => (
                                <li key={g.id}>{g.texto}</li>
                            ))}
                            {alertas.grupos_sin_clases.length === 0 && <li>Ninguno</li>}
                        </ul>
                    </div>

                    <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="text-sm font-semibold text-slate-900 dark:text-white">
                            Docentes sin disponibilidad registrada
                        </h2>
                        <ul className="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                            {alertas.docentes_sin_disponibilidad.map((nombre) => (
                                <li key={nombre}>{nombre}</li>
                            ))}
                            {alertas.docentes_sin_disponibilidad.length === 0 && <li>Ninguno</li>}
                        </ul>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
