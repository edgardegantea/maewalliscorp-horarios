import PrimaryButton from '@/Components/PrimaryButton';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ roles }) {
    const eliminar = (role) => {
        if (confirm(`¿Eliminar el rol "${role.nombre}"?`)) {
            router.delete(route('admin.roles.destroy', role.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Roles y permisos</h2>}>
            <Head title="Roles y permisos" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Roles y permisos' }]}
                    title="Roles y permisos"
                    description="Crea roles personalizados con permisos granulares por módulo y asígnalos a usuarios desde la sección Usuarios."
                    actions={
                        <Link href={route('admin.roles.create')}>
                            <PrimaryButton>Nuevo rol</PrimaryButton>
                        </Link>
                    }
                />

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Slug</TH>
                                <TH align="right">Permisos</TH>
                                <TH align="right">Usuarios</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {roles.map((role) => (
                                <TR key={role.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">
                                        {role.nombre}
                                        {role.es_sistema && (
                                            <span className="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                sistema
                                            </span>
                                        )}
                                    </TD>
                                    <TD>{role.slug}</TD>
                                    <TD align="right">{role.permissions_count}</TD>
                                    <TD align="right">{role.users_count}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.roles.edit', role.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            {!role.es_sistema && (
                                                <button
                                                    onClick={() => eliminar(role)}
                                                    className="font-medium text-red-600 hover:text-red-800"
                                                >
                                                    Eliminar
                                                </button>
                                            )}
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {roles.length === 0 && <EmptyRow colSpan={5}>No hay roles registrados.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
