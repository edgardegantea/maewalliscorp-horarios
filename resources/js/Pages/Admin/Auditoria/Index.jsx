import SelectInput from '@/Components/SelectInput';
import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const COLOR_ACCION = {
    crear: 'green',
    actualizar: 'indigo',
    eliminar: 'red',
};

const ETIQUETA_ACCION = {
    crear: 'Creó',
    actualizar: 'Actualizó',
    eliminar: 'Eliminó',
};

export default function Index({ registros, usuarios, entidades, filtros }) {
    const filtrar = (cambios) => {
        router.get(
            route('admin.auditoria.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.usuario || filtros.accion || filtros.entidad;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Auditoría</h2>}>
            <Head title="Auditoría" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Auditoría' }]}
                    title="Auditoría"
                    description="Historial de cambios realizados en docentes, disponibilidad y cargas académicas."
                />

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Usuario</label>
                            <SelectInput
                                className="mt-1 block w-56"
                                value={filtros.usuario ?? ''}
                                onChange={(e) => filtrar({ usuario: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                {usuarios.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Acción</label>
                            <SelectInput
                                className="mt-1 block w-40"
                                value={filtros.accion ?? ''}
                                onChange={(e) => filtrar({ accion: e.target.value || undefined })}
                            >
                                <option value="">Todas</option>
                                <option value="crear">Creó</option>
                                <option value="actualizar">Actualizó</option>
                                <option value="eliminar">Eliminó</option>
                            </SelectInput>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Entidad</label>
                            <SelectInput
                                className="mt-1 block w-44"
                                value={filtros.entidad ?? ''}
                                onChange={(e) => filtrar({ entidad: e.target.value || undefined })}
                            >
                                <option value="">Todas</option>
                                {entidades.map((e) => (
                                    <option key={e} value={e}>
                                        {e}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.auditoria.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {registros.total} {registros.total === 1 ? 'registro' : 'registros'}
                        </span>
                    </div>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Fecha</TH>
                                <TH>Usuario</TH>
                                <TH>Acción</TH>
                                <TH>Detalle</TH>
                            </TR>
                        </THead>
                        <TBody>
                            {registros.data.map((registro) => (
                                <TR key={registro.id}>
                                    <TD className="whitespace-nowrap text-slate-500 dark:text-slate-400">
                                        {new Date(registro.created_at).toLocaleString('es-MX', {
                                            dateStyle: 'short',
                                            timeStyle: 'short',
                                        })}
                                    </TD>
                                    <TD className="font-medium text-slate-900 dark:text-white">
                                        {registro.usuario?.name ?? 'Sistema'}
                                    </TD>
                                    <TD>
                                        <Badge color={COLOR_ACCION[registro.accion] ?? 'slate'}>
                                            {ETIQUETA_ACCION[registro.accion] ?? registro.accion}
                                        </Badge>
                                    </TD>
                                    <TD>{registro.descripcion}</TD>
                                </TR>
                            ))}
                            {registros.data.length === 0 && (
                                <EmptyRow colSpan={4}>
                                    {hayFiltros ? 'No hay registros que coincidan con los filtros.' : 'No hay actividad registrada todavía.'}
                                </EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>

                {(registros.prev_page_url || registros.next_page_url) && (
                    <div className="flex justify-end gap-3 text-sm">
                        <Link
                            href={registros.prev_page_url ?? '#'}
                            className={`font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 ${!registros.prev_page_url ? 'pointer-events-none opacity-40' : ''}`}
                        >
                            ← Anterior
                        </Link>
                        <Link
                            href={registros.next_page_url ?? '#'}
                            className={`font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 ${!registros.next_page_url ? 'pointer-events-none opacity-40' : ''}`}
                        >
                            Siguiente →
                        </Link>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
