import DisponibilidadEditor from '@/Components/Disponibilidad/DisponibilidadEditor';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Disponibilidad({ docente, periodo, periodos, bloques }) {
    const cambiarPeriodo = (periodoId) => {
        router.get(route('admin.docentes.disponibilidad.edit', [docente.id, periodoId]));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Disponibilidad de {docente.user.name}</h2>}>
            <Head title="Disponibilidad del docente" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Docentes', href: route('admin.docentes.index') },
                        { label: docente.user.name, href: route('admin.docentes.edit', docente.id) },
                        { label: 'Disponibilidad' },
                    ]}
                    title={`Disponibilidad de ${docente.user.name}`}
                    actions={
                        <Link
                            href={route('admin.docentes.edit', docente.id)}
                            className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            ← Volver al docente
                        </Link>
                    }
                />
                <Card>
                    <DisponibilidadEditor
                        key={periodo.id}
                        action={route('admin.docentes.disponibilidad.update', docente.id)}
                        periodo={periodo}
                        periodos={periodos}
                        bloques={bloques}
                        onPeriodoChange={cambiarPeriodo}
                    />
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
