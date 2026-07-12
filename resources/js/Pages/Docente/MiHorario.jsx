import SelectInput from '@/Components/SelectInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';

const DIAS = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

const siguiente = (hora) => `${String(parseInt(hora.slice(0, 2), 10) + 1).padStart(2, '0')}:00`;
const aMin = (h) => parseInt(h.slice(0, 2), 10) * 60 + parseInt(h.slice(3, 5), 10);

export default function MiHorario({ periodo, periodos, slots, cargas }) {
    const cargaEn = (dia, hora) => {
        const ini = aMin(hora);
        const fin = ini + 60;
        return cargas.find(
            (c) => c.dia_semana === dia && aMin(c.hora_inicio) < fin && aMin(c.hora_fin) > ini,
        );
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Mi horario</h2>}>
            <Head title="Mi horario" />

            <div className="space-y-6">
                <PageHeader title="Mi horario" description="Tu carga académica asignada para el periodo seleccionado." />

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
                                                        className={`h-12 border border-slate-200 px-1 text-center align-middle text-[11px] leading-tight dark:border-slate-700 ${
                                                            carga ? 'bg-indigo-50 dark:bg-indigo-500/10' : 'bg-white dark:bg-slate-900'
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
            </div>
        </AuthenticatedLayout>
    );
}
