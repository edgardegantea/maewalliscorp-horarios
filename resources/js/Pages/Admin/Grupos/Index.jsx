import ImportCsvButton from '@/Components/ImportCsvButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { EmptyRow, TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

const SEMESTRES = Array.from({ length: 12 }, (_, i) => i + 1);

export default function Index({ grupos, periodos, carreras, modalidades, filtros }) {
    const eliminar = (grupo) => {
        if (confirm(`¿Eliminar el grupo "${grupo.nombre}"? Esto también eliminará sus cargas académicas.`)) {
            router.delete(route('admin.grupos.destroy', grupo.id));
        }
    };

    const filtrar = (cambios) => {
        router.get(
            route('admin.grupos.index'),
            { ...filtros, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hayFiltros = filtros.periodo || filtros.carrera || filtros.semestre || filtros.modalidad;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Grupos</h2>}>
            <Head title="Grupos" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Grupos' }]}
                    title="Grupos"
                    description="Grupos de alumnos por carrera y periodo escolar."
                    actions={
                        <>
                            <ImportCsvButton
                                action={route('admin.grupos.import')}
                                columnas={[
                                    'carrera_clave',
                                    'periodo_nombre',
                                    'nombre',
                                    'semestre (opcional)',
                                    'matricula',
                                    'modalidad (opcional)',
                                    'hora_inicio (opcional)',
                                    'hora_fin (opcional)',
                                ]}
                                nota="carrera_clave y periodo_nombre deben coincidir exactamente con registros existentes."
                            />
                            <Link href={route('admin.grupos.create')}>
                                <PrimaryButton>Nuevo grupo</PrimaryButton>
                            </Link>
                        </>
                    }
                />

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo escolar</label>
                            <SelectInput
                                className="mt-1 block w-48"
                                value={filtros.periodo ?? ''}
                                onChange={(e) => filtrar({ periodo: e.target.value || undefined })}
                            >
                                <option value="">Todos</option>
                                {periodos.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.nombre}
                                    </option>
                                ))}
                            </SelectInput>
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

                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Modalidad</label>
                            <SelectInput
                                className="mt-1 block w-44"
                                value={filtros.modalidad ?? ''}
                                onChange={(e) => filtrar({ modalidad: e.target.value || undefined })}
                            >
                                <option value="">Todas</option>
                                {modalidades.map((m) => (
                                    <option key={m} value={m}>
                                        {m}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        {hayFiltros && (
                            <Link
                                href={route('admin.grupos.index')}
                                className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                            >
                                Limpiar filtros
                            </Link>
                        )}

                        <span className="ml-auto text-sm text-slate-400 dark:text-slate-500">
                            {grupos.length} {grupos.length === 1 ? 'grupo' : 'grupos'}
                        </span>
                    </div>
                </Card>

                <Card padded={false}>
                    <Table>
                        <THead>
                            <TR>
                                <TH>Nombre</TH>
                                <TH>Carrera</TH>
                                <TH>Periodo</TH>
                                <TH>Semestre</TH>
                                <TH>Matrícula</TH>
                                <TH>Modalidad</TH>
                                <TH>Horario</TH>
                                <TH align="right">
                                    <span className="sr-only">Acciones</span>
                                </TH>
                            </TR>
                        </THead>
                        <TBody>
                            {grupos.map((grupo) => (
                                <TR key={grupo.id}>
                                    <TD className="font-medium text-slate-900">{grupo.nombre}</TD>
                                    <TD>{grupo.carrera.nombre}</TD>
                                    <TD>{grupo.periodo_escolar.nombre}</TD>
                                    <TD>{grupo.semestre ?? '—'}</TD>
                                    <TD>{grupo.matricula}</TD>
                                    <TD>{grupo.modalidad}</TD>
                                    <TD>
                                        {grupo.hora_inicio && grupo.hora_fin
                                            ? `${grupo.hora_inicio.slice(0, 5)} - ${grupo.hora_fin.slice(0, 5)}`
                                            : '—'}
                                    </TD>
                                    <TD align="right">
                                        <div className="flex justify-end gap-4">
                                            <Link
                                                href={route('admin.grupos.edit', grupo.id)}
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
                            {grupos.length === 0 && <EmptyRow colSpan={8}>No hay grupos que coincidan con los filtros.</EmptyRow>}
                        </TBody>
                    </Table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
