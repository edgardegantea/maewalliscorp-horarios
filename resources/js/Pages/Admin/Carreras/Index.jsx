import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ carreras }) {
    const eliminar = (carrera) => {
        if (confirm(`¿Eliminar la carrera "${carrera.nombre}"? Esto también eliminará sus asignaturas, grupos y cargas académicas.`)) {
            router.delete(route('admin.carreras.destroy', carrera.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Carreras</h2>}>
            <Head title="Carreras" />

            <div className="space-y-6">
                <PageHeader
                    title="Carreras"
                    description="Programas académicos ofrecidos por la institución."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.carreras.import')}
                                columnas={['nombre', 'clave', 'activo (opcional)']}
                            />
                            <Link href={route('admin.carreras.create')}>
                                <PrimaryButton>Nueva carrera</PrimaryButton>
                            </Link>
                        </>
                    }
                />

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Clave</TH>
                                <TH>Estado</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {carreras.map((carrera) => (
                                <TR key={carrera.id}>
                                    <TD className="font-medium text-slate-900">{carrera.nombre}</TD>
                                    <TD>{carrera.clave}</TD>
                                    <TD>{carrera.activo && <Badge color="green">Activa</Badge>}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.carreras.edit', carrera.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(carrera)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {carreras.length === 0 && (
                                <EmptyRow colSpan={4}>No hay carreras registradas.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
