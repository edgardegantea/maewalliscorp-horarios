import DisponibilidadEditor from '@/Components/Disponibilidad/DisponibilidadEditor';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function Disponibilidad({ periodo, periodos, bloques, diasNoLaborables }) {
    const cambiarPeriodo = (periodoId) => {
        router.get(route('docente.disponibilidad.edit', { periodo: periodoId }));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi disponibilidad</h2>}>
            <Head title="Mi disponibilidad" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Mi disponibilidad' }]}
                    title="Mi disponibilidad"
                    description="Registra los horarios en los que puedes impartir clases."
                />

                {diasNoLaborables?.length > 0 && (
                    <div className="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                        <p className="font-medium">Días no laborables en este periodo:</p>
                        <p className="mt-1">
                            {diasNoLaborables.map((d) => `${d.fecha} — ${d.descripcion}`).join(' · ')}
                        </p>
                        <p className="mt-1 text-xs">
                            Tu disponibilidad se registra por día de la semana; no se imparten clases en estas fechas
                            aunque caigan en un día que marques disponible.
                        </p>
                    </div>
                )}

                <Card>
                    {periodo ? (
                        <DisponibilidadEditor
                            key={periodo.id}
                            action={route('docente.disponibilidad.update')}
                            periodo={periodo}
                            periodos={periodos}
                            bloques={bloques}
                            onPeriodoChange={cambiarPeriodo}
                        />
                    ) : (
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            No hay periodos escolares registrados. Contacta al administrador.
                        </p>
                    )}
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
