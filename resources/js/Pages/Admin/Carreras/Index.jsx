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

export default function Index({ carreras, filtros }) {
    const [q, setQ] = useBusqueda('admin.carreras.index', filtros);

    const eliminar = (carrera) => {
        if (confirm(`¿Eliminar la carrera "${carrera.nombre}"? Esto también eliminará sus asignaturas, grupos y cargas académicas.`)) {
            router.delete(route('admin.carreras.destroy', carrera.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.carreras.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.activo;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Carreras</h2>}>
            <Head title="Carreras" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Carreras' }]}
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

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Buscar</label>
                            <TextInput
                                className="mt-1 block w-56"
                                placeholder="Nombre o clave…"
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
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.carreras.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {carreras.length} {carreras.length === 1 ? 'carrera' : 'carreras'}
                        </span>
                    </div>
                </Card>

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
                                <EmptyRow colSpan={4}>No hay carreras que coincidan con los filtros.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
