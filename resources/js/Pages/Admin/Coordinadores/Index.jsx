import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ coordinadores, carreras, filtros }) {
    const [q, setQ] = useBusqueda('admin.coordinadores.index', filtros);

    const eliminar = (coordinador) => {
        if (confirm(`¿Eliminar al coordinador "${coordinador.name}"? Esto también eliminará su acceso al sistema.`)) {
            router.delete(route('admin.coordinadores.destroy', coordinador.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.coordinadores.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.carrera;

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

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Buscar</label>
                            <TextInput
                                className="mt-1 block w-64"
                                placeholder="Nombre, usuario o correo…"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Carrera a cargo</label>
                            <SelectInput
                                className="mt-1 block w-56"
                                value={filtros.carrera ?? ''}
                                onChange={(e) => filtrar({ carrera: e.target.value || undefined })}
                            >
                                <option value="">Todas</option>
                                {carreras.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.coordinadores.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {coordinadores.length} {coordinadores.length === 1 ? 'coordinador' : 'coordinadores'}
                        </span>
                    </div>
                </Card>

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
                                    {hayFiltros
                                        ? 'No hay coordinadores que coincidan con los filtros.'
                                        : 'No hay coordinadores registrados. Asigna carreras a un coordinador desde la ficha de la carrera.'}
                                </EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
