import PrimaryButton from '@/Components/PrimaryButton';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ grupos }) {
    const eliminar = (grupo) => {
        if (confirm(`¿Eliminar el grupo "${grupo.nombre}"?`)) {
            router.delete(route('admin.grupos-usuarios.destroy', grupo.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Grupos de usuarios</h2>}>
            <Head title="Grupos de usuarios" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Grupos de usuarios' }]}
                    title="Grupos de usuarios"
                    description="Organiza usuarios en grupos (por campus, departamento, etc.) para gestionarlos en conjunto."
                    actions={
                        <Link href={route('admin.grupos-usuarios.create')}>
                            <PrimaryButton>Nuevo grupo</PrimaryButton>
                        </Link>
                    }
                />

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Descripción</TH>
                                <TH align="right">Miembros</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {grupos.map((grupo) => (
                                <TR key={grupo.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">{grupo.nombre}</TD>
                                    <TD>{grupo.descripcion ?? '—'}</TD>
                                    <TD align="right">{grupo.usuarios_count}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.grupos-usuarios.edit', grupo.id)}
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
                            {grupos.length === 0 && <EmptyRow colSpan={4}>No hay grupos registrados.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
