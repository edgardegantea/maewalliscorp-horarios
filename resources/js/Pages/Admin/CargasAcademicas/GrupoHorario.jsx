import Icon from '@/Components/Icon';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { secondaryLinkClasses } from '@/buttonStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Fragment, useMemo, useState } from 'react';

const DIAS = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 7, label: 'Domingo' },
];

const DIA_SABADO = 6;

const siguienteHora = (hora) => {
    const h = parseInt(hora.slice(0, 2), 10) + 1;
    return `${String(h).padStart(2, '0')}:00`;
};

// Paleta de colores para diferenciar asignaturas a simple vista; se asigna
// por orden de aparición y se repite (módulo) si hay más asignaturas que
// colores en la paleta.
const PALETA = [
    { bg: 'bg-indigo-100 dark:bg-indigo-500/20', ring: 'ring-indigo-500' },
    { bg: 'bg-emerald-100 dark:bg-emerald-500/20', ring: 'ring-emerald-500' },
    { bg: 'bg-amber-100 dark:bg-amber-500/20', ring: 'ring-amber-500' },
    { bg: 'bg-rose-100 dark:bg-rose-500/20', ring: 'ring-rose-500' },
    { bg: 'bg-sky-100 dark:bg-sky-500/20', ring: 'ring-sky-500' },
    { bg: 'bg-violet-100 dark:bg-violet-500/20', ring: 'ring-violet-500' },
    { bg: 'bg-lime-100 dark:bg-lime-500/20', ring: 'ring-lime-500' },
    { bg: 'bg-cyan-100 dark:bg-cyan-500/20', ring: 'ring-cyan-500' },
    { bg: 'bg-fuchsia-100 dark:bg-fuchsia-500/20', ring: 'ring-fuchsia-500' },
    { bg: 'bg-orange-100 dark:bg-orange-500/20', ring: 'ring-orange-500' },
];

function CeldaGrupo({ celda, color, resaltada, atenuada, onHover, onSalir, periodo, carrera }) {
    if (!celda?.ocupado) {
        return <td className="h-14 border border-slate-200 dark:border-slate-700" />;
    }

    const editar = () => {
        router.visit(
            route('admin.cargas.builder', {
                periodo,
                carrera: celda.carrera_id ?? carrera,
                docente: celda.docente_id,
                editar: celda.carga_id,
                dia: celda.dia_semana,
                hora_inicio: celda.hora_inicio,
                hora_fin: celda.hora_fin,
                asignatura_id: celda.asignatura_id,
                aula_id: celda.aula_id,
                grupo_ids: celda.grupo_ids.join(','),
            }),
        );
    };

    return (
        <td
            role="button"
            tabIndex={0}
            title="Editar esta clase"
            onMouseEnter={() => onHover(celda.asignatura_id)}
            onMouseLeave={onSalir}
            onClick={editar}
            onKeyDown={(e) => (e.key === 'Enter' || e.key === ' ') && editar()}
            className={`group relative h-14 cursor-pointer border border-slate-200 px-1 text-center align-middle text-[11px] leading-tight transition dark:border-slate-700 ${color.bg} ${
                resaltada ? `z-10 shadow-md ring-2 ${color.ring}` : ''
            } ${atenuada ? 'opacity-30' : ''}`}
        >
            <Icon
                name="pencil"
                className="pointer-events-none absolute right-1 top-1 h-3 w-3 text-slate-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-slate-500"
            />
            <div className="truncate font-medium text-slate-800 dark:text-slate-100">{celda.asignatura}</div>
            <div className="truncate text-slate-500 dark:text-slate-400">{celda.docente}</div>
            <div className="truncate text-slate-400 dark:text-slate-500">{celda.aula}</div>
        </td>
    );
}

export default function GrupoHorario({ grupo, slots, dias }) {
    const [asignaturaResaltada, setAsignaturaResaltada] = useState(null);

    const mapa = {};
    dias.forEach((d) => {
        mapa[d.dia_semana] = { 1: {}, 2: {} };
        d.horas.forEach((h, idx) => {
            mapa[d.dia_semana][1][idx] = h;
        });
        (d.horas_modulo2 ?? []).forEach((h, idx) => {
            mapa[d.dia_semana][2][idx] = h;
        });
    });

    // Asigna un color de la paleta a cada asignatura distinta, por orden de
    // aparición en el horario, para poder distinguirlas de un vistazo.
    const colorPorAsignatura = useMemo(() => {
        const colores = {};
        let siguiente = 0;
        Object.values(mapa).forEach((modulos) => {
            Object.values(modulos).forEach((horas) => {
                Object.values(horas).forEach((h) => {
                    if (h?.ocupado && !(h.asignatura_id in colores)) {
                        colores[h.asignatura_id] = PALETA[siguiente % PALETA.length];
                        siguiente += 1;
                    }
                });
            });
        });
        return colores;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [dias]);

    // Recorta la tabla al rango de horas realmente usado por el grupo, en vez
    // de mostrar siempre el bloque completo 07:00-21:00.
    let primerIdx = -1;
    let ultimoIdx = -1;
    slots.forEach((_, idx) => {
        const ocupada = Object.values(mapa).some((modulos) => Object.values(modulos).some((horas) => horas[idx]?.ocupado));
        if (ocupada) {
            if (primerIdx === -1) primerIdx = idx;
            ultimoIdx = idx;
        }
    });
    const slotsVisibles = primerIdx === -1 ? slots : slots.slice(primerIdx, ultimoIdx + 1);
    const offset = primerIdx === -1 ? 0 : primerIdx;

    // Los grupos sabatinos (terminados en "F") pueden combinar días entre
    // semana y sábado con módulos, así que ahí solo se muestran los días con
    // clase asignada. Los grupos regulares siempre muestran Lunes a Viernes
    // (su semana normal), y sábado/domingo solo si tienen algo asignado.
    const esSabatino = /f$/i.test(grupo.nombre.trim());
    const diasInfo = DIAS.map((d) => ({
        ...d,
        modulo1: Object.values(mapa[d.value]?.[1] ?? {}).some((h) => h?.ocupado),
        modulo2: d.value === DIA_SABADO && Object.values(mapa[d.value]?.[2] ?? {}).some((h) => h?.ocupado),
    })).filter((d) => {
        if (!esSabatino && d.value >= 1 && d.value <= 5) {
            return true;
        }
        return d.modulo1 || d.modulo2;
    });

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Horario del grupo {grupo.nombre}</h2>}>
            <Head title={`Horario · Grupo ${grupo.nombre}`}>
                <style>{'@media print { @page { size: landscape; } }'}</style>
            </Head>

            <div className="space-y-6 print:space-y-2">
                <div className="print:hidden">
                    <PageHeader
                        breadcrumbs={[
                            { label: 'Dashboard', href: route('dashboard') },
                            { label: 'Cargas académicas', href: route('admin.cargas.index', { periodo: grupo.periodo_escolar_id, carrera: grupo.carrera_id }) },
                            { label: `Grupo ${grupo.nombre}` },
                        ]}
                        title={`Horario del grupo ${grupo.nombre}`}
                        description={`${grupo.carrera.nombre} · ${grupo.periodo_escolar.nombre} · Semestre ${grupo.semestre}`}
                        actions={
                            <div className="flex gap-3">
                                <Link
                                    href={route('admin.cargas.index', { periodo: grupo.periodo_escolar_id, carrera: grupo.carrera_id })}
                                    className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                >
                                    ← Volver al listado
                                </Link>
                                <button type="button" onClick={() => window.print()} className={secondaryLinkClasses}>
                                    Imprimir / Exportar PDF
                                </button>
                            </div>
                        }
                    />
                </div>

                <div className="hidden print:block print:text-center">
                    <h1 className="text-lg font-semibold">Horario del grupo {grupo.nombre}</h1>
                    <p className="text-sm text-slate-600">
                        {grupo.carrera.nombre} · {grupo.periodo_escolar.nombre} · Semestre {grupo.semestre}
                    </p>
                </div>

                <Card>
                    <div className="overflow-x-auto">
                        <table className="w-full table-fixed border-collapse">
                            <colgroup>
                                <col className="w-24" />
                                {diasInfo.map((d) =>
                                    d.value === DIA_SABADO ? (
                                        <Fragment key={d.value}>
                                            {d.modulo1 && <col />}
                                            {d.modulo2 && <col />}
                                        </Fragment>
                                    ) : (
                                        <col key={d.value} />
                                    ),
                                )}
                            </colgroup>
                            <thead>
                                <tr>
                                    <th rowSpan={2} className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        Hora
                                    </th>
                                    {diasInfo.map((d) => (
                                        <th
                                            key={d.value}
                                            colSpan={d.value === DIA_SABADO ? Number(d.modulo1) + Number(d.modulo2) : 1}
                                            rowSpan={d.value === DIA_SABADO && d.modulo1 && d.modulo2 ? 1 : 2}
                                            className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {d.label}
                                        </th>
                                    ))}
                                </tr>
                                <tr>
                                    {diasInfo
                                        .filter((d) => d.value === DIA_SABADO && d.modulo1 && d.modulo2)
                                        .flatMap((d) => [
                                            <th key={`${d.value}-1`} className="border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-normal text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                                Mód. 1
                                            </th>,
                                            <th key={`${d.value}-2`} className="border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-normal text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                                Mód. 2
                                            </th>,
                                        ])}
                                </tr>
                            </thead>
                            <tbody>
                                {slotsVisibles.map((hora, i) => {
                                    const idx = offset + i;
                                    return (
                                        <tr key={hora}>
                                            <td className="border border-slate-200 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                                {hora}–{siguienteHora(hora)}
                                            </td>
                                            {diasInfo.map((d) => {
                                                const celdaProps = (celda) => {
                                                    const id = celda?.asignatura_id;
                                                    return {
                                                        celda,
                                                        color: colorPorAsignatura[id] ?? PALETA[0],
                                                        resaltada: id != null && id === asignaturaResaltada,
                                                        atenuada: asignaturaResaltada != null && id !== asignaturaResaltada,
                                                        onHover: setAsignaturaResaltada,
                                                        onSalir: () => setAsignaturaResaltada(null),
                                                        periodo: grupo.periodo_escolar_id,
                                                        carrera: grupo.carrera_id,
                                                    };
                                                };

                                                return d.value !== DIA_SABADO ? (
                                                    <CeldaGrupo key={`${d.value}-${hora}`} {...celdaProps(mapa[d.value]?.[1]?.[idx])} />
                                                ) : (
                                                    <Fragment key={`${d.value}-${hora}`}>
                                                        {d.modulo1 && <CeldaGrupo key={`${d.value}-1-${hora}`} {...celdaProps(mapa[d.value]?.[1]?.[idx])} />}
                                                        {d.modulo2 && <CeldaGrupo key={`${d.value}-2-${hora}`} {...celdaProps(mapa[d.value]?.[2]?.[idx])} />}
                                                    </Fragment>
                                                );
                                            })}
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
