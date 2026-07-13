import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ periodos, filtros }) {
    const [q, setQ] = useBusqueda('admin.periodos.index', filtros);

    const eliminar = (periodo) => {
        if (confirm(`¿Eliminar el periodo "${periodo.nombre}"? Esto también eliminará sus cargas académicas.`)) {
            router.delete(route('admin.periodos.destroy', periodo.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.periodos.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.activo;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Periodos escolares</h2>}>
            <Head title="Periodos escolares" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Periodos escolares' }]}
                    title="Periodos escolares"
                    description="Administra los ciclos escolares en los que se organizan las cargas académicas."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.periodos.import')}
                                columnas={['nombre', 'fecha_inicio', 'fecha_fin', 'activo (opcional)']}
                                nota="Las fechas deben tener formato AAAA-MM-DD."
                            />
                            <Link href={route('admin.periodos.create')}>
                                <PrimaryButton>Nuevo periodo</PrimaryButton>
                            </Link>
                        </>
                    }
                />

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Buscar</label>
                            <TextInput
                                className="mt-1 block w-56"
                                placeholder="Nombre…"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Estado</label>
                            <SelectInput
                                className="mt-1 block w-36"
                                value={filtros.activo ?? ''}
                                onChange={(e) => filtrar({ activo: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.periodos.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {periodos.length} {periodos.length === 1 ? 'periodo' : 'periodos'}
                        </span>
                    </div>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Inicio</TH>
                                <TH>Fin</TH>
                                <TH>Estado</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {periodos.map((periodo) => (
                                <TR key={periodo.id}>
                                    <TD className="font-medium text-slate-900">{periodo.nombre}</TD>
                                    <TD>{periodo.fecha_inicio}</TD>
                                    <TD>{periodo.fecha_fin}</TD>
                                    <TD>{periodo.activo && <Badge color="green">Activo</Badge>}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.periodos.edit', periodo.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(periodo)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {periodos.length === 0 && (
                                <EmptyRow colSpan={5}>No hay periodos escolares que coincidan con los filtros.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
