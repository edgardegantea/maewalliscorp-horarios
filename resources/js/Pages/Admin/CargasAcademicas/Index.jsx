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

const ESTADO_BADGE = {
    pendiente: { color: 'slate', label: 'Pendiente' },
    confirmada: { color: 'green', label: 'Confirmada' },
    conflicto: { color: 'red', label: 'Con problema' },
};

function GrupoSection({ item, periodo, carrera }) {
    const { grupo, cargas } = item;
    const [abierto, setAbierto] = useState(false);

    const eliminar = (carga) => {
        if (confirm('¿Eliminar esta carga académica?')) {
            router.delete(route('admin.cargas.destroy', carga.id), { preserveScroll: true });
        }
    };

    return (
        <Card padded={false}>
            <div className="flex w-full items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-slate-800">
                <button type="button" onClick={() => setAbierto((a) => !a)} className="flex flex-1 items-center gap-2 text-left">
                    <svg
                        className={`h-4 w-4 shrink-0 text-slate-400 transition-transform dark:text-slate-500 ${abierto ? 'rotate-90' : ''}`}
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                    </svg>
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Grupo {grupo.nombre}</h3>
                    {grupo.semestre && <Badge color="slate">Semestre {grupo.semestre}</Badge>}
                    <Badge color="indigo">{grupo.matricula} alumnos</Badge>
                </button>
                <div className="flex items-center gap-4">
                    <span className="text-xs text-slate-400 dark:text-slate-500">
                        {cargas.length} {cargas.length === 1 ? 'clase asignada' : 'clases asignadas'}
                    </span>
                    <Link
                        href={route('admin.cargas.grupo-horario', grupo.id)}
                        className="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        Ver horario
                    </Link>
                    <Link
                        href={route('admin.cargas.builder', { periodo, carrera, grupo: grupo.id })}
                        className="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        Agregar clase
                    </Link>
                </div>
            </div>

            {abierto && (cargas.length === 0 ? (
                <p className="px-4 py-6 text-sm text-slate-400 dark:text-slate-500">Sin cargas académicas asignadas todavía.</p>
            ) : (
                <Table>
                    <THead>
                        <TR>
                            <TH>Día</TH>
                            <TH>Horario</TH>
                            <TH>Docente</TH>
                            <TH>Asignatura</TH>
                            <TH>Grupos</TH>
                            <TH>Aula</TH>
                            <TH>Estado</TH>
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
                                <TD>{carga.grupos.map((g) => g.nombre).join(' / ')}</TD>
                                <TD>{carga.aula.nombre}</TD>
                                <TD>
                                    <Badge color={ESTADO_BADGE[carga.estado].color}>{ESTADO_BADGE[carga.estado].label}</Badge>
                                    {carga.estado === 'conflicto' && carga.comentario_docente && (
                                        <p className="mt-1 max-w-xs text-xs text-red-600 dark:text-red-400">{carga.comentario_docente}</p>
                                    )}
                                </TD>
                                <TD align="right">
                                    <div className="flex justify-end gap-4">
                                        <Link
                                            href={route('admin.cargas.builder', {
                                                periodo,
                                                carrera,
                                                docente: carga.docente_id,
                                                editar: carga.id,
                                                dia: carga.dia_semana,
                                                hora_inicio: carga.hora_inicio.slice(0, 5),
                                                hora_fin: carga.hora_fin.slice(0, 5),
                                                asignatura_id: carga.asignatura_id,
                                                aula_id: carga.aula_id,
                                                grupo_ids: carga.grupos.map((g) => g.id).join(','),
                                            })}
                                            className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            Editar
                                        </Link>
                                        <button
                                            onClick={() => eliminar(carga)}
                                            className="font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </TD>
                            </TR>
                        ))}
                    </TBody>
                </Table>
            ))}
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
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Cargas académicas' }]}
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
                            <GrupoSection key={item.grupo.id} item={item} periodo={periodo} carrera={carrera} />
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
