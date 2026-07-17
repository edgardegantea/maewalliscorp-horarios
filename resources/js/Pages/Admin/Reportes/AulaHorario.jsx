import Icon from '@/Components/Icon';
import SelectInput from '@/Components/SelectInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
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

// Misma paleta que el horario de grupo, para que asignaturas distintas se
// distingan a simple vista.
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

function CeldaAula({ celda, color, resaltada, atenuada, onHover, onSalir, periodo }) {
    if (!celda?.ocupado) {
        return <td className="h-16 border border-slate-200 dark:border-slate-700" />;
    }

    const editar = () => {
        router.visit(
            route('admin.cargas.builder', {
                periodo,
                carrera: celda.carrera_id,
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
            className={`group relative h-16 cursor-pointer border border-slate-200 px-1 text-center align-middle text-[11px] leading-tight transition dark:border-slate-700 ${color.bg} ${
                resaltada ? `z-10 shadow-md ring-2 ${color.ring}` : ''
            } ${atenuada ? 'opacity-30' : ''}`}
        >
            <Icon
                name="pencil"
                className="pointer-events-none absolute right-1 top-1 h-3 w-3 text-slate-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-slate-500"
            />
            <div className="truncate font-medium text-slate-800 dark:text-slate-100">{celda.asignatura}</div>
            <div className="truncate text-slate-500 dark:text-slate-400">{celda.docente}</div>
            <div className="truncate text-slate-400 dark:text-slate-500">
                {celda.grupo}
                {celda.carreras.length > 0 ? ` · ${celda.carreras.join(', ')}` : ''}
            </div>
        </td>
    );
}

export default function AulaHorario({ aula, aulas, periodo, periodos, slots, dias }) {
    const [asignaturaResaltada, setAsignaturaResaltada] = useState(null);

    const indiceActual = aulas.findIndex((a) => a.id === aula.id);
    const anterior = indiceActual > 0 ? aulas[indiceActual - 1] : null;
    const siguiente = indiceActual !== -1 && indiceActual < aulas.length - 1 ? aulas[indiceActual + 1] : null;

    const irAAula = (aulaId) => {
        if (aulaId) {
            router.visit(route('admin.reportes.aula-horario', { aula: aulaId, periodo: periodo?.id }));
        }
    };

    const cambiarPeriodo = (periodoId) => {
        router.visit(route('admin.reportes.aula-horario', { aula: aula.id, periodo: periodoId || undefined }));
    };

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

    // Recorta la tabla al rango de horas realmente usado por el aula, en vez
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

    const diasInfo = DIAS.map((d) => ({
        ...d,
        modulo1: Object.values(mapa[d.value]?.[1] ?? {}).some((h) => h?.ocupado),
        modulo2: d.value === DIA_SABADO && Object.values(mapa[d.value]?.[2] ?? {}).some((h) => h?.ocupado),
    })).filter((d) => {
        if (d.value >= 1 && d.value <= 5) {
            return true;
        }
        return d.modulo1 || d.modulo2;
    });

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Horario del aula {aula.nombre}</h2>}>
            <Head title={`Horario · Aula ${aula.nombre}`}>
                <style>{'@media print { @page { size: landscape; } }'}</style>
            </Head>

            <div className="space-y-6 print:space-y-2">
                <div className="print:hidden">
                    <PageHeader
                        breadcrumbs={[
                            { label: 'Dashboard', href: route('dashboard') },
                            { label: 'Utilización de aulas', href: route('admin.reportes.utilizacion-aulas', { periodo: periodo?.id }) },
                            { label: `Aula ${aula.nombre}` },
                        ]}
                        title={`Horario del aula ${aula.nombre}`}
                        description={
                            periodo
                                ? `${periodo.nombre} · carrera, grupo, asignatura y docente que la ocupan`
                                : 'Selecciona un periodo escolar para ver su ocupación.'
                        }
                        actions={
                            <div className="flex flex-wrap items-center gap-3">
                                <Link
                                    href={route('admin.reportes.utilizacion-aulas', { periodo: periodo?.id })}
                                    className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                                >
                                    ← Volver al listado
                                </Link>
                                <button type="button" onClick={() => window.print()} className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                    <Icon name="download" className="h-4 w-4" />
                                    Imprimir / Exportar PDF
                                </button>
                            </div>
                        }
                    />
                </div>

                <Card className="print:hidden">
                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo escolar</label>
                    <SelectInput className="mt-1 block w-64" value={periodo?.id ?? ''} onChange={(e) => cambiarPeriodo(e.target.value)}>
                        <option value="">Selecciona un periodo</option>
                        {periodos.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.nombre}
                            </option>
                        ))}
                    </SelectInput>
                </Card>

                {aulas.length > 1 && (
                    <div className="flex items-center justify-between gap-3 print:hidden">
                        <button
                            type="button"
                            onClick={() => irAAula(anterior?.id)}
                            disabled={!anterior}
                            className="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-30 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            <Icon name="chevronRight" className="h-4 w-4 rotate-180" />
                            {anterior ? `Aula ${anterior.nombre}` : 'Aula anterior'}
                        </button>

                        <div className="flex items-center gap-2">
                            <span className="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Ver aula</span>
                            <SelectInput className="block w-44" value={aula.id} onChange={(e) => irAAula(e.target.value)}>
                                {aulas.map((a) => (
                                    <option key={a.id} value={a.id}>
                                        {a.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        <button
                            type="button"
                            onClick={() => irAAula(siguiente?.id)}
                            disabled={!siguiente}
                            className="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-30 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            {siguiente ? `Aula ${siguiente.nombre}` : 'Siguiente aula'}
                            <Icon name="chevronRight" className="h-4 w-4" />
                        </button>
                    </div>
                )}

                <div className="hidden print:block print:text-center">
                    <p className="text-sm text-slate-600">
                        Aula {aula.nombre} · {periodo?.nombre}
                    </p>
                </div>

                {!periodo ? (
                    <p className="text-sm text-slate-500 dark:text-slate-400">Selecciona un periodo escolar para ver el horario del aula.</p>
                ) : primerIdx === -1 ? (
                    <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">
                        Esta aula no tiene ninguna clase asignada en {periodo.nombre}.
                    </p>
                ) : (
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
                                                            periodo: periodo?.id,
                                                        };
                                                    };

                                                    return d.value !== DIA_SABADO ? (
                                                        <CeldaAula key={`${d.value}-${hora}`} {...celdaProps(mapa[d.value]?.[1]?.[idx])} />
                                                    ) : (
                                                        <Fragment key={`${d.value}-${hora}`}>
                                                            {d.modulo1 && <CeldaAula key={`${d.value}-1-${hora}`} {...celdaProps(mapa[d.value]?.[1]?.[idx])} />}
                                                            {d.modulo2 && <CeldaAula key={`${d.value}-2-${hora}`} {...celdaProps(mapa[d.value]?.[2]?.[idx])} />}
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
                )}
            </div>
        </AuthenticatedLayout>
    );
}
