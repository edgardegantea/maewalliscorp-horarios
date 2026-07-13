import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import useBusqueda from '@/Hooks/useBusqueda';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const SEMESTRES = Array.from({ length: 12 }, (_, i) => i + 1);

export default function Index({ asignaturas, carreras, filtros }) {
    const [q, setQ] = useBusqueda('admin.asignaturas.index', filtros);

    const eliminar = (asignatura) => {
        if (confirm(`¿Eliminar la asignatura "${asignatura.nombre}"?`)) {
            router.delete(route('admin.asignaturas.destroy', asignatura.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.asignaturas.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.q || filtros.carrera || filtros.semestre;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Asignaturas</h2>}>
            <Head title="Asignaturas" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Asignaturas' }]}
                    title="Asignaturas"
                    description="Materias que se imparten dentro de cada carrera."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.asignaturas.import')}
                                columnas={['carrera_clave', 'nombre', 'clave', 'semestre (opcional)', 'horas_semana (opcional)']}
                                nota="carrera_clave debe coincidir con la clave de una carrera existente."
                            />
                            <Link href={route('admin.asignaturas.create')}>
                                <PrimaryButton>Nueva asignatura</PrimaryButton>
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
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Carrera</label>
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

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Semestre</label>
                            <SelectInput
                                className="mt-1 block w-32"
                                value={filtros.semestre ?? ''}
                                onChange={(e) => filtrar({ semestre: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                {SEMESTRES.map((s) => (
                                    <option key={s} value={s}>
                                        {s}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.asignaturas.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {asignaturas.length} {asignaturas.length === 1 ? 'asignatura' : 'asignaturas'}
                        </span>
                    </div>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Carrera</TH>
                                <TH>Clave</TH>
                                <TH>Semestre</TH>
                                <TH>Horas/semana</TH>
                                <TH>Módulo sabatino</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {asignaturas.map((asignatura) => (
                                <TR key={asignatura.id}>
                                    <TD className="font-medium text-slate-900">{asignatura.nombre}</TD>
                                    <TD>{asignatura.carrera.nombre}</TD>
                                    <TD>{asignatura.clave}</TD>
                                    <TD>{asignatura.semestre ?? '—'}</TD>
                                    <TD>{asignatura.horas_semana ?? '—'}</TD>
                                    <TD>{asignatura.modulo_sabatino ? `Módulo ${asignatura.modulo_sabatino}` : '—'}</TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.asignaturas.edit', asignatura.id)}
                                                className="font-medium text-indigo-600 hover:text-indigo-800"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                onClick={() => eliminar(asignatura)}
                                                className="font-medium text-red-600 hover:text-red-800"
                                            >
                                                Eliminar
                                            </button>
                                        </div>
                                    </TD>
                                </TR>
                            ))}
                            {asignaturas.length === 0 && (
                                <EmptyRow colSpan={7}>No hay asignaturas que coincidan con los filtros.</EmptyRow>
                            )}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
