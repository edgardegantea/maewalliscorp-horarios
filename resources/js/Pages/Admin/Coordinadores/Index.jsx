import PrimaryButton from '@/Components/PrimaryButton';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ coordinadores }) {
    const eliminar = (coordinador) => {
        if (confirm(`¿Eliminar al coordinador "${coordinador.name}"? Esto también eliminará su acceso al sistema.`)) {
            router.delete(route('admin.coordinadores.destroy', coordinador.id));
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Coordinadores</h2>}>
            <Head title="Coordinadores" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Coordinadores' }]}
                    title="Coordinadores"
                    description="Cuentas con acceso limitado a las carreras que se les asignen (grupos, asignaturas y cargas académicas)."
                    actions={
                        <Link href={route('admin.coordinadores.create')}>
                            <PrimaryButton>Nuevo coordinador</PrimaryButton>
                        </Link>
                    }
                />

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Usuario</TH>
                                <TH>Correo</TH>
                                <TH>Carreras a cargo</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {coordinadores.map((coordinador) => (
                                <TR key={coordinador.id}>
                                    <TD className="font-medium text-slate-900 dark:text-white">{coordinador.name}</TD>
                                    <TD>{coordinador.username ?? '—'}</TD>
                                    <TD>{coordinador.email}</TD>
                                    <TD>
                                        {coordinador.carreras_coordinadas.length === 0
                                            ? '—'
                                            : coordinador.carreras_coordinadas.map((c) => c.nombre).join(', ')}
                                    </TD>
                                    <TD align="right">
                                        <button
                                            onClick={() => eliminar(coordinador)}
                                            className="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Eliminar
                                        </button>
                                    </TD>
                                </TR>
                            ))}
                            {coordinadores.length === 0 && (
                                <EmptyRow colSpan={5}>
                                    No hay coordinadores registrados. Asigna carreras a un coordinador desde la
                                    ficha de la carrera.
                                </EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
