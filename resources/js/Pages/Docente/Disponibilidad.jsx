import DisponibilidadEditor from '@/Components/Disponibilidad/DisponibilidadEditor';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

export default function Disponibilidad({ periodo, periodos, bloques }) {
    const cambiarPeriodo = (periodoId) => {
        router.get(route('docente.disponibilidad.edit', { periodo: periodoId }));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi disponibilidad</h2>}>
            <Head title="Mi disponibilidad" />

            <div className="max-w-3xl space-y-6">
                <PageHeader
                    title="Mi disponibilidad"
                    description="Registra los horarios en los que puedes impartir clases."
                />
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
