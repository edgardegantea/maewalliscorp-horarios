import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ aulas }) {
    const eliminar = (aula) => {
        if (confirm(`¿Eliminar el aula "${aula.nombre}"?`)) {
            router.delete(route('admin.aulas.destroy', aula.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Aulas</h2>}>
            <Head title="Aulas" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Aulas' }]}
                    title="Aulas"
                    description="Espacios físicos disponibles para impartir clases."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.aulas.import')}
                                columnas={['nombre', 'capacidad (opcional)', 'tipo (opcional)', 'activo (opcional)']}
                            />
                            <Link href={route('admin.aulas.create')}>
                                <PrimaryButton>Nueva aula</PrimaryButton>
                            </Link>
                        </>
                    }
                />

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Capacidad</TH>
                                <TH>Tipo</TH>
                                <TH>Estado</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {aulas.map((aula) => (
                                <TR key={aula.id}>
                                    <TD className="font-medium text-slate-900">{aula.nombre}</TD>
                                    <TD>{aula.capacidad ?? '—'}</TD>
                                    <TD>{aula.tipo ?? '—'}</TD>
                                    <TD>{aula.activo && <Badge color="green">Activa</Badge>}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.aulas.edit', aula.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(aula)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {aulas.length === 0 && <EmptyRow colSpan={5}>No hay aulas registradas.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
