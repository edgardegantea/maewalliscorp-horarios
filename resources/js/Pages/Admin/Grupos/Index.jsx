import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ grupos }) {
    const eliminar = (grupo) => {
        if (confirm(`¿Eliminar el grupo "${grupo.nombre}"? Esto también eliminará sus cargas académicas.`)) {
            router.delete(route('admin.grupos.destroy', grupo.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Grupos</h2>}>
            <Head title="Grupos" />

            <div className="space-y-6">
                <PageHeader
                    title="Grupos"
                    description="Grupos de alumnos por carrera y periodo escolar."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.grupos.import')}
                                columnas={[
                                    'carrera_clave',
                                    'periodo_nombre',
                                    'nombre',
                                    'semestre (opcional)',
                                    'matricula',
                                    'modalidad (opcional)',
                                ]}
                                nota="carrera_clave y periodo_nombre deben coincidir exactamente con registros existentes."
                            />
                            <Link href={route('admin.grupos.create')}>
                                <PrimaryButton>Nuevo grupo</PrimaryButton>
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
                                <TH>Periodo</TH>
                                <TH>Semestre</TH>
                                <TH>Matrícula</TH>
                                <TH>Modalidad</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {grupos.map((grupo) => (
                                <TR key={grupo.id}>
                                    <TD className="font-medium text-slate-900">{grupo.nombre}</TD>
                                    <TD>{grupo.carrera.nombre}</TD>
                                    <TD>{grupo.periodo_escolar.nombre}</TD>
                                    <TD>{grupo.semestre ?? '—'}</TD>
                                    <TD>{grupo.matricula}</TD>
                                    <TD>{grupo.modalidad}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.grupos.edit', grupo.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(grupo)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {grupos.length === 0 && <EmptyRow colSpan={7}>No hay grupos registrados.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
