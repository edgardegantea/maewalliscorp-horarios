import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import { secondaryLinkClasses } from '@/buttonStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

function GrupoSection({ item }) {
    const { grupo, cargas } = item;

    const eliminar = (carga) => {
        if (confirm('¿Eliminar esta carga académica?')) {
            router.delete(route('admin.cargas.destroy', carga.id), { preserveScroll: true });
        }
    };

    return (
        <Card padded={false}>
            <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <div className="flex items-center gap-2">
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Grupo {grupo.nombre}</h3>
                    {grupo.semestre && <Badge color="slate">Semestre {grupo.semestre}</Badge>}
                    <Badge color="indigo">{grupo.matricula} alumnos</Badge>
                </div>
                <span className="text-xs text-slate-400 dark:text-slate-500">
                    {cargas.length} {cargas.length === 1 ? 'clase asignada' : 'clases asignadas'}
                </span>
            </div>

            {cargas.length === 0 ? (
                <p className="px-4 py-6 text-sm text-slate-400 dark:text-slate-500">Sin cargas académicas asignadas todavía.</p>
            ) : (
                <Table>
                    <THead>
                        <TR>
                            <TH>Día</TH>
                            <TH>Horario</TH>
                            <TH>Docente</TH>
                            <TH>Asignatura</TH>
                            <TH>Aula</TH>
                            <TH align="right">
                                <span className="sr-only">Acciones</span>
                            </TH>
                        </TR>
                    </THead>
                    <TBody>
                        {cargas.map((carga) => (
                            <TR key={carga.id}>
                                <TD className="font-medium text-slate-900 dark:text-white">{DIAS[carga.dia_semana]}</TD>
                                <TD>
                                    {carga.hora_inicio.slice(0, 5)} - {carga.hora_fin.slice(0, 5)}
                                </TD>
                                <TD>{carga.docente.user.name}</TD>
                                <TD>{carga.asignatura.nombre}</TD>
                                <TD>{carga.aula.nombre}</TD>
                                <TD align="right">
                                    <button
                                        onClick={() => eliminar(carga)}
                                        className="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Eliminar
                                    </button>
                                </TD>
                            </TR>
                        ))}
                    </TBody>
                </Table>
            )}
        </Card>
    );
}

export default function Index({ periodos, carreras, periodoSeleccionado, carreraSeleccionada, grupos }) {
    const [periodo, setPeriodo] = useState(periodoSeleccionado ?? '');
    const [carrera, setCarrera] = useState(carreraSeleccionada ?? '');

    const filtrar = (nuevoPeriodo, nuevaCarrera) => {
        router.get(
            route('admin.cargas.index'),
            { periodo: nuevoPeriodo || undefined, carrera: nuevaCarrera || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const puedeCrear = periodo && carrera;

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Cargas académicas</h2>}>
            <Head title="Cargas académicas" />

            <div className="space-y-6">
                <PageHeader
                    title="Cargas académicas"
                    description="Organizadas por periodo escolar, carrera y grupo."
                />

                <Card>
                    <div className="flex flex-wrap items-end gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo escolar</label>
                            <SelectInput
                                className="mt-1 block w-64"
                                value={periodo}
                                onChange={(e) => {
                                    setPeriodo(e.target.value);
                                    filtrar(e.target.value, carrera);
                                }}
                            >
                                <option value="">Selecciona un periodo</option>
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
                                className="mt-1 block w-64"
                                value={carrera}
                                onChange={(e) => {
                                    setCarrera(e.target.value);
                                    filtrar(periodo, e.target.value);
                                }}
                            >
                                <option value="">Selecciona una carrera</option>
                                {carreras.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        <div className="ml-auto flex gap-3">
                            {periodo && route().has('admin.concentrado.general') && (
                                <a
                                    href={route('admin.concentrado.general', { periodo })}
                                    className={secondaryLinkClasses}
                                >
                                    Exportar concentrado general
                                </a>
                            )}
                            {puedeCrear && route().has('admin.concentrado.export') && (
                                <a
                                    href={route('admin.concentrado.export', { periodo, carrera })}
                                    className={secondaryLinkClasses}
                                >
                                    Exportar carrera a Excel
                                </a>
                            )}
                            <Link
                                href={puedeCrear ? route('admin.cargas.builder', { periodo, carrera }) : '#'}
                                className={puedeCrear ? '' : 'pointer-events-none opacity-50'}
                            >
                                <PrimaryButton disabled={!puedeCrear}>Nueva carga académica</PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </Card>

                {puedeCrear ? (
                    <div className="space-y-6">
                        {grupos.map((item) => (
                            <GrupoSection key={item.grupo.id} item={item} />
                        ))}
                        {grupos.length === 0 && (
                            <Card>
                                <p className="text-center text-sm text-slate-400 dark:text-slate-500">
                                    No hay grupos registrados para esta carrera y periodo.
                                </p>
                            </Card>
                        )}
                    </div>
                ) : (
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Selecciona un periodo escolar y una carrera para ver y crear cargas académicas.
                    </p>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
