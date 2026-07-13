import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ aulas, tipos, filtros }) {
    const [q, setQ] = useBusqueda('admin.aulas.index', filtros);

    const eliminar = (aula) => {
        if (confirm(`¿Eliminar el aula "${aula.nombre}"?`)) {
            router.delete(route('admin.aulas.destroy', aula.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.aulas.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.tipo || filtros.activo;

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
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Tipo</label>
                            <SelectInput
                                className="mt-1 block w-44"
                                value={filtros.tipo ?? ''}
                                onChange={(e) => filtrar({ tipo: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                {tipos.map((t) => (
                                    <option key={t} value={t}>
                                        {t}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Estado</label>
                            <SelectInput
                                className="mt-1 block w-36"
                                value={filtros.activo ?? ''}
                                onChange={(e) => filtrar({ activo: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.aulas.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {aulas.length} {aulas.length === 1 ? 'aula' : 'aulas'}
                        </span>
                    </div>
                </Card>

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
                            {aulas.length === 0 && <EmptyRow colSpan={5}>No hay aulas que coincidan con los filtros.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
