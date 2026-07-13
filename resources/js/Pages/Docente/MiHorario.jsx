import Badge from '@/Components/ui/Badge';
import SelectInput from '@/Components/SelectInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import { TBody, TD, TH, THead, TR, Table } from '@/Components/ui/Table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

const DIAS = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

const NOMBRE_DIA = Object.fromEntries(DIAS.map((d) => [d.value, d.label]));

const ESTADO_BADGE = {
    pendiente: { color: 'amber', label: 'Pendiente' },
    confirmada: { color: 'green', label: 'Confirmada' },
    conflicto: { color: 'red', label: 'Con problema' },
};

const BORDE_ESTADO = {
    pendiente: 'border-l-amber-400',
    confirmada: 'border-l-emerald-500',
    conflicto: 'border-l-red-500',
};

const siguiente = (hora) => `${String(parseInt(hora.slice(0, 2), 10) + 1).padStart(2, '0')}:00`;
const aMin = (h) => parseInt(h.slice(0, 2), 10) * 60 + parseInt(h.slice(3, 5), 10);

export default function MiHorario({ periodo, periodos, slots, cargas }) {
    const [reportando, setReportando] = useState(null);
    const [comentario, setComentario] = useState('');

    const cargaEn = (dia, hora) => {
        const ini = aMin(hora);
        const fin = ini + 60;
        return cargas.find(
            (c) => c.dia_semana === dia && aMin(c.hora_inicio) < fin && aMin(c.hora_fin) > ini,
        );
    };

    const confirmar = (carga) => {
        router.put(route('docente.horario.estado', carga.id), { estado: 'confirmada' }, { preserveScroll: true });
    };

    const enviarReporte = (carga) => {
        if (!comentario.trim()) {
            return;
        }
        router.put(
            route('docente.horario.estado', carga.id),
            { estado: 'conflicto', comentario_docente: comentario },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setReportando(null);
                    setComentario('');
                },
            },
        );
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi horario</h2>}>
            <Head title="Mi horario" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[{ label: 'Dashboard', href: route('dashboard') }, { label: 'Mi horario' }]}
                    title="Mi horario"
                    description="Tu carga académica asignada para el periodo seleccionado. Confirma cada clase o reporta un problema."
                />

                {periodos.length > 0 && (
                    <Card className="max-w-xs">
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Periodo escolar</label>
                        <SelectInput
                            className="mt-1 block w-full"
                            value={periodo?.id ?? ''}
                            onChange={(e) => router.get(route('docente.horario', { periodo: e.target.value }))}
                        >
                            {periodos.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.nombre}
                                </option>
                            ))}
                        </SelectInput>
                    </Card>
                )}

                <Card padded={false}>
                    {cargas.length === 0 ? (
                        <p className="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
                            No tienes clases asignadas en este periodo.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full table-fixed border-collapse">
                                <colgroup>
                                    <col className="w-28" />
                                    {DIAS.map((d) => (
                                        <col key={d.value} />
                                    ))}
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">Hora</th>
                                        {DIAS.map((d) => (
                                            <th key={d.value} className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                {d.label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {slots.map((hora) => (
                                        <tr key={hora}>
                                            <td className="border border-slate-200 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                                {hora}–{siguiente(hora)}
                                            </td>
                                            {DIAS.map((d) => {
                                                const carga = cargaEn(d.value, hora);
                                                return (
                                                    <td
                                                        key={d.value}
                                                        className={`h-12 border border-slate-200 border-l-4 px-1 text-center align-middle text-[11px] leading-tight dark:border-slate-700 ${
                                                            carga
                                                                ? `bg-indigo-50 dark:bg-indigo-500/10 ${BORDE_ESTADO[carga.estado]}`
                                                                : 'border-l-transparent bg-white dark:bg-slate-900'
                                                        }`}
                                                    >
                                                        {carga && (
                                                            <div className="truncate">
                                                                <div className="font-medium text-slate-800 dark:text-slate-100">{carga.asignatura}</div>
                                                                <div className="text-slate-500 dark:text-slate-400">
                                                                    {carga.grupo} · {carga.aula}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </td>
                                                );
                                            })}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>

                {cargas.length > 0 && (
                    <Card padded={false}>
                        <Table>
                            <THead>
                                <TR>
                                    <TH>Día</TH>
                                    <TH>Horario</TH>
                                    <TH>Asignatura</TH>
                                    <TH>Grupo(s)</TH>
                                    <TH>Aula</TH>
                                    <TH>Estado</TH>
                                    <TH align="right">
                                        <span className="sr-only">Acciones</span>
                                    </TH>
                                </TR>
                            </THead>
                            <TBody>
                                {cargas.map((carga) => (
                                    <>
                                        <TR key={carga.id}>
                                            <TD>{NOMBRE_DIA[carga.dia_semana]}</TD>
                                            <TD>
                                                {carga.hora_inicio} - {carga.hora_fin}
                                            </TD>
                                            <TD className="font-medium text-slate-900 dark:text-white">{carga.asignatura}</TD>
                                            <TD>{carga.grupo}</TD>
                                            <TD>{carga.aula}</TD>
                                            <TD>
                                                <Badge color={ESTADO_BADGE[carga.estado].color}>{ESTADO_BADGE[carga.estado].label}</Badge>
                                            </TD>
                                            <TD align="right">
                                                <div className="flex justify-end gap-3">
                                                    {carga.estado !== 'confirmada' && (
                                                        <button
                                                            onClick={() => confirmar(carga)}
                                                            className="font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400"
                                                        >
                                                            Confirmar
                                                        </button>
                                                    )}
                                                    <button
                                                        onClick={() => {
                                                            setReportando(carga.id === reportando ? null : carga.id);
                                                            setComentario(carga.comentario_docente ?? '');
                                                        }}
                                                        className="font-medium text-red-600 hover:text-red-800 dark:text-red-400"
                                                    >
                                                        Reportar problema
                                                    </button>
                                                </div>
                                            </TD>
                                        </TR>
                                        {reportando === carga.id && (
                                            <tr>
                                                <td colSpan={7} className="bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
                                                    <textarea
                                                        className="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                        rows={2}
                                                        placeholder="Describe el problema (choca con otra actividad, aula incorrecta, etc.)"
                                                        value={comentario}
                                                        onChange={(e) => setComentario(e.target.value)}
                                                    />
                                                    <div className="mt-2 flex justify-end gap-3">
                                                        <button
                                                            type="button"
                                                            onClick={() => setReportando(null)}
                                                            className="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400"
                                                        >
                                                            Cancelar
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => enviarReporte(carga)}
                                                            disabled={!comentario.trim()}
                                                            className="text-sm font-medium text-red-600 hover:text-red-800 disabled:opacity-40 dark:text-red-400"
                                                        >
                                                            Enviar reporte
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </>
                                ))}
                            </TBody>
                        </Table>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
