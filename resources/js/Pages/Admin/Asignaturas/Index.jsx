import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ asignaturas }) {
    const eliminar = (asignatura) => {
        if (confirm(`¿Eliminar la asignatura "${asignatura.nombre}"?`)) {
            router.delete(route('admin.asignaturas.destroy', asignatura.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Asignaturas</h2>}>
            <Head title="Asignaturas" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Asignaturas' }]}
                    title="Asignaturas"
                    description="Materias que se imparten dentro de cada carrera."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.asignaturas.import')}
                                columnas={['carrera_clave', 'nombre', 'clave', 'horas_semana (opcional)']}
                                nota="carrera_clave debe coincidir con la clave de una carrera existente."
                            />
                            <Link href={route('admin.asignaturas.create')}>
                                <PrimaryButton>Nueva asignatura</PrimaryButton>
                            </Link>
                        </>
                    }
                />

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Carrera</TH>
                                <TH>Clave</TH>
                                <TH>Horas/semana</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {asignaturas.map((asignatura) => (
                                <TR key={asignatura.id}>
                                    <TD className="font-medium text-slate-900">{asignatura.nombre}</TD>
                                    <TD>{asignatura.carrera.nombre}</TD>
                                    <TD>{asignatura.clave}</TD>
                                    <TD>{asignatura.horas_semana ?? '—'}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.asignaturas.edit', asignatura.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(asignatura)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {asignaturas.length === 0 && (
                                <EmptyRow colSpan={5}>No hay asignaturas registradas.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
