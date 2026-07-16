import Icon from '@/Components/Icon';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import Badge from '@/Components/ui/Badge';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import { secondaryLinkClasses } from '@/buttonStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

const ESTADO_BADGE = {
    pendiente: { color: 'slate', label: 'Pendiente' },
    confirmada: { color: 'green', label: 'Confirmada' },
    conflicto: { color: 'red', label: 'Con problema' },
};

function GrupoSection({ item, periodo, mostrarCarrera }) {
    const { grupo, cargas } = item;
    const [abierto, setAbierto] = useState(false);

    const eliminar = (carga) => {
        if (confirm('¿Eliminar esta carga académica?')) {
            router.delete(route('admin.cargas.destroy', carga.id), { preserveScroll: true });
        }
    };

    return (
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
            <div className="flex w-full items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40">
                <button type="button" onClick={() => setAbierto((a) => !a)} className="flex flex-1 flex-wrap items-center gap-2 text-left">
                    <Icon
                        name="chevronRight"
                        className={`h-4 w-4 shrink-0 text-slate-400 transition-transform dark:text-slate-500 ${abierto ? 'rotate-90' : ''}`}
                    />
                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                        <Icon name="academicCap" className="h-4 w-4" />
                    </span>
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Grupo {grupo.nombre}</h3>
                    {mostrarCarrera && <Badge color="indigo">{grupo.carrera.nombre}</Badge>}
                    {grupo.semestre && <Badge color="slate">Semestre {grupo.semestre}</Badge>}
                    <Badge color="slate">{grupo.matricula} alumnos</Badge>
                </button>
                <div className="flex shrink-0 items-center gap-4">
                    <span
                        className={`text-xs font-medium ${cargas.length === 0 ? 'text-slate-400 dark:text-slate-500' : 'text-slate-500 dark:text-slate-400'}`}
                    >
                        {cargas.length} {cargas.length === 1 ? 'clase asignada' : 'clases asignadas'}
                    </span>
                    <Link
                        href={route('admin.cargas.grupo-horario', grupo.id)}
                        className="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        <Icon name="calendar" className="h-3.5 w-3.5" />
                        Ver horario
                    </Link>
                    <Link
                        href={route('admin.cargas.builder', { periodo, carrera: grupo.carrera_id, grupo: grupo.id })}
                        className="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                        <Icon name="plus" className="h-3.5 w-3.5" />
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
                                                carrera: grupo.carrera_id,
                                                docente: carga.docente_id,
                                                editar: carga.id,
                                                dia: carga.dia_semana,
                                                hora_inicio: carga.hora_inicio.slice(0, 5),
                                                hora_fin: carga.hora_fin.slice(0, 5),
                                                asignatura_id: carga.asignatura_id,
                                                aula_id: carga.aula_id,
                                                grupo_ids: carga.grupos.map((g) => g.id).join(','),
                                                modulo_sabatino: carga.modulo_sabatino || undefined,
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
        </div>
    );
}

function FiltroLabel({ children }) {
    return <label className="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{children}</label>;
}

export default function Index({ periodos, carreras, asignaturas, docentes, periodoSeleccionado, carreraSeleccionada, filtros, grupos }) {
    const [periodo, setPeriodo] = useState(periodoSeleccionado ?? '');
    const [carrera, setCarrera] = useState(carreraSeleccionada ?? '');
    const [asignatura, setAsignatura] = useState(filtros?.asignatura ?? '');
    const [docente, setDocente] = useState(filtros?.docente ?? '');
    const [grupoTexto, setGrupoTexto] = useState(filtros?.grupo ?? '');
    const [estado, setEstado] = useState(filtros?.estado ?? '');
    const primerRender = useRef(true);
    const debounce = useRef(null);

    const filtrar = (valores) => {
        router.get(route('admin.cargas.index'), valores, { preserveState: true, preserveScroll: true, replace: true });
    };

    const valoresActuales = () => ({
        periodo: periodo || undefined,
        carrera: carrera || undefined,
        asignatura: asignatura || undefined,
        docente: docente || undefined,
        grupo: grupoTexto || undefined,
        estado: estado || undefined,
    });

    // El buscador de grupo se aplica con un pequeño debounce para no disparar
    // una petición por cada tecla.
    useEffect(() => {
        if (primerRender.current) {
            primerRender.current = false;
            return;
        }
        clearTimeout(debounce.current);
        debounce.current = setTimeout(() => filtrar(valoresActuales()), 350);
        return () => clearTimeout(debounce.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [grupoTexto]);

    const actualizar = (campo, valor) => {
        const setters = { periodo: setPeriodo, carrera: setCarrera, asignatura: setAsignatura, docente: setDocente, estado: setEstado };
        setters[campo](valor);
        // Cambiar de periodo o carrera invalida los demás filtros dependientes.
        const base = { ...valoresActuales(), [campo]: valor || undefined };
        if (campo === 'periodo' || campo === 'carrera') {
            base.asignatura = undefined;
            base.docente = undefined;
            base.grupo = undefined;
            base.estado = undefined;
            setAsignatura('');
            setDocente('');
            setGrupoTexto('');
            setEstado('');
        }
        filtrar(base);
    };

    const limpiarFiltros = () => {
        setAsignatura('');
        setDocente('');
        setGrupoTexto('');
        setEstado('');
        filtrar({ periodo: periodo || undefined, carrera: carrera || undefined });
    };

    const puedeVer = Boolean(periodo);
    const puedeCrear = periodo && carrera;
    const hayFiltrosSecundarios = asignatura || docente || grupoTexto || estado;

    const totalClases = grupos.reduce((acc, item) => acc + item.cargas.length, 0);

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Cargas académicas</h2>}>
            <Head title="Cargas académicas" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Cargas académicas' }]}
                    title="Cargas académicas"
                    description="Organizadas por periodo escolar, carrera y grupo."
                    actions={
                        <div className="flex flex-wrap items-center gap-3">
                            {periodo && route().has('admin.concentrado.general') && (
                                <a
                                    href={route('admin.concentrado.general', { periodo })}
                                    className={`${secondaryLinkClasses} gap-1.5`}
                                >
                                    <Icon name="download" className="h-4 w-4" />
                                    Concentrado general
                                </a>
                            )}
                            {periodo && route().has('admin.concentrado.por-campus') && (
                                <a
                                    href={route('admin.concentrado.por-campus', { periodo })}
                                    className={`${secondaryLinkClasses} gap-1.5`}
                                >
                                    <Icon name="download" className="h-4 w-4" />
                                    Concentrado por campus
                                </a>
                            )}
                            {puedeCrear && route().has('admin.concentrado.export') && (
                                <a
                                    href={route('admin.concentrado.export', { periodo, carrera })}
                                    className={`${secondaryLinkClasses} gap-1.5`}
                                >
                                    <Icon name="grid" className="h-4 w-4" />
                                    Exportar a Excel
                                </a>
                            )}
                            <Link
                                href={puedeCrear ? route('admin.cargas.builder', { periodo, carrera }) : '#'}
                                className={puedeCrear ? '' : 'pointer-events-none opacity-50'}
                            >
                                <PrimaryButton disabled={!puedeCrear} className="gap-1.5">
                                    <Icon name="plus" className="h-4 w-4" />
                                    Nueva carga académica
                                </PrimaryButton>
                            </Link>
                        </div>
                    }
                />

                <Card className="!p-0">
                    <div className="flex flex-wrap items-end gap-4 p-5">
                        <div className="flex items-center gap-2 self-center text-slate-400 dark:text-slate-500">
                            <Icon name="funnel" className="h-4 w-4" />
                        </div>
                        <div className="min-w-[12rem]">
                            <FiltroLabel>Periodo escolar</FiltroLabel>
                            <SelectInput className="block w-full" value={periodo} onChange={(e) => actualizar('periodo', e.target.value)}>
                                <option value="">Selecciona un periodo</option>
                                {periodos.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        <div className="min-w-[14rem]">
                            <FiltroLabel>Carrera</FiltroLabel>
                            <SelectInput className="block w-full" value={carrera} onChange={(e) => actualizar('carrera', e.target.value)}>
                                <option value="">Todas las carreras</option>
                                {carreras.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>
                    </div>

                    {/* Los filtros de detalle solo tienen sentido (y opciones que mostrar)
                        una vez que se acota por carrera; antes de eso quedarían vacíos o
                        con demasiadas opciones mezcladas de todas las carreras. */}
                    {carrera && (
                        <div className="flex flex-wrap items-end gap-4 border-t border-slate-100 bg-slate-50/60 p-5 dark:border-slate-800 dark:bg-slate-800/30">
                            <div className="min-w-[12rem] flex-1">
                                <FiltroLabel>Asignatura</FiltroLabel>
                                <SelectInput className="block w-full" value={asignatura} onChange={(e) => actualizar('asignatura', e.target.value)}>
                                    <option value="">Todas</option>
                                    {asignaturas.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.nombre}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>

                            <div className="min-w-[12rem] flex-1">
                                <FiltroLabel>Docente</FiltroLabel>
                                <SelectInput className="block w-full" value={docente} onChange={(e) => actualizar('docente', e.target.value)}>
                                    <option value="">Todos</option>
                                    {docentes.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nombre}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>

                            <div className="min-w-[10rem] flex-1">
                                <FiltroLabel>Grupo</FiltroLabel>
                                <div className="relative">
                                    <Icon name="search" className="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <TextInput
                                        className="block w-full pl-8"
                                        placeholder="p. ej. 1A"
                                        value={grupoTexto}
                                        onChange={(e) => setGrupoTexto(e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="min-w-[9rem]">
                                <FiltroLabel>Estado</FiltroLabel>
                                <SelectInput className="block w-full" value={estado} onChange={(e) => actualizar('estado', e.target.value)}>
                                    <option value="">Todos</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="conflicto">Con problema</option>
                                </SelectInput>
                            </div>

                            {hayFiltrosSecundarios && (
                                <button
                                    type="button"
                                    onClick={limpiarFiltros}
                                    className="mb-0.5 inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                >
                                    <Icon name="xMark" className="h-3.5 w-3.5" />
                                    Limpiar filtros
                                </button>
                            )}
                        </div>
                    )}
                </Card>

                {puedeVer ? (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between px-1 text-xs text-slate-500 dark:text-slate-400">
                            <span>
                                {grupos.length} {grupos.length === 1 ? 'grupo' : 'grupos'} · {totalClases} {totalClases === 1 ? 'clase asignada' : 'clases asignadas'}
                            </span>
                        </div>

                        <div className="space-y-4">
                            {grupos.map((item) => (
                                <GrupoSection key={item.grupo.id} item={item} periodo={periodo} mostrarCarrera={!carrera} />
                            ))}
                            {grupos.length === 0 && (
                                <Card>
                                    <p className="text-center text-sm text-slate-400 dark:text-slate-500">
                                        {hayFiltrosSecundarios
                                            ? 'Ningún grupo coincide con los filtros aplicados.'
                                            : `No hay grupos registrados para ${carrera ? 'esta carrera y periodo' : 'este periodo'}.`}
                                    </p>
                                </Card>
                            )}
                        </div>
                    </div>
                ) : (
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Selecciona un periodo escolar para ver y crear cargas académicas.
                    </p>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
