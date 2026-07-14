import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { secondaryLinkClasses } from '@/buttonStyles';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Fragment } from 'react';

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

function CeldaGrupo({ celda, hora }) {
    if (!celda?.ocupado) {
        return <td className="h-14 border border-slate-200 dark:border-slate-700" />;
    }

    return (
        <td className="h-14 border border-slate-200 bg-indigo-50 px-1 text-center align-middle text-[11px] leading-tight dark:border-slate-700 dark:bg-indigo-500/10">
            <div className="truncate font-medium text-slate-800 dark:text-slate-100">{celda.asignatura}</div>
            <div className="truncate text-slate-500 dark:text-slate-400">{celda.docente}</div>
            <div className="truncate text-slate-400 dark:text-slate-500">{celda.aula}</div>
        </td>
    );
}

export default function GrupoHorario({ grupo, slots, dias }) {
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

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Horario del grupo {grupo.nombre}</h2>}>
            <Head title={`Horario · Grupo ${grupo.nombre}`} />

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
                                {DIAS.map((d) =>
                                    d.value === DIA_SABADO ? (
                                        <Fragment key={d.value}>
                                            <col />
                                            <col />
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
                                    {DIAS.map((d) => (
                                        <th
                                            key={d.value}
                                            colSpan={d.value === DIA_SABADO ? 2 : 1}
                                            rowSpan={d.value === DIA_SABADO ? 1 : 2}
                                            className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            {d.label}
                                        </th>
                                    ))}
                                </tr>
                                <tr>
                                    <th className="border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-normal text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        Mód. 1
                                    </th>
                                    <th className="border border-slate-200 bg-slate-50 px-2 py-1 text-[10px] font-normal text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                        Mód. 2
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {slots.map((hora, idx) => (
                                    <tr key={hora}>
                                        <td className="border border-slate-200 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                            {hora}–{siguienteHora(hora)}
                                        </td>
                                        {DIAS.map((d) =>
                                            d.value !== DIA_SABADO ? (
                                                <CeldaGrupo key={`${d.value}-${hora}`} celda={mapa[d.value]?.[1]?.[idx]} hora={hora} />
                                            ) : (
                                                <Fragment key={`${d.value}-${hora}`}>
                                                    <CeldaGrupo celda={mapa[d.value]?.[1]?.[idx]} hora={hora} />
                                                    <CeldaGrupo celda={mapa[d.value]?.[2]?.[idx]} hora={hora} />
                                                </Fragment>
                                            ),
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
