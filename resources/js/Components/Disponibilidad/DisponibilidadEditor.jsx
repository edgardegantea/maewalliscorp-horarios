import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';

const DIAS = [
    { value: 1, label: 'Lunes' },
    { value: 2, label: 'Martes' },
    { value: 3, label: 'Miércoles' },
    { value: 4, label: 'Jueves' },
    { value: 5, label: 'Viernes' },
    { value: 6, label: 'Sábado' },
    { value: 7, label: 'Domingo' },
];

// Recorta "HH:MM:SS" a "HH:MM" para los inputs type=time.
const hhmm = (hora) => (hora ? hora.slice(0, 5) : '');

// Minutos entre dos horas "HH:MM"; 0 si el bloque está incompleto o es inválido.
function minutosBloque(bloque) {
    if (!bloque.hora_inicio || !bloque.hora_fin) {
        return 0;
    }
    const [hi, mi] = bloque.hora_inicio.split(':').map(Number);
    const [hf, mf] = bloque.hora_fin.split(':').map(Number);
    const minutos = hf * 60 + mf - (hi * 60 + mi);

    return minutos > 0 ? minutos : 0;
}

const formatoHoras = (minutos) => {
    const horas = minutos / 60;

    return Number.isInteger(horas) ? `${horas}` : horas.toFixed(1);
};

function agrupar(bloques) {
    const porDia = {};
    DIAS.forEach((d) => (porDia[d.value] = []));
    bloques.forEach((b) => {
        porDia[b.dia_semana].push({ hora_inicio: hhmm(b.hora_inicio), hora_fin: hhmm(b.hora_fin) });
    });
    return porDia;
}

export default function DisponibilidadEditor({
    action,
    method = 'put',
    periodo,
    periodos,
    bloques,
    onPeriodoChange,
    extraFields = {},
}) {
    const inicial = useMemo(() => agrupar(bloques), [bloques]);

    const { data, setData, transform, submit, processing, errors } = useForm({
        periodo_escolar_id: periodo?.id ?? '',
        dias: inicial,
        ...extraFields,
    });

    const agregarBloque = (dia) => {
        setData('dias', {
            ...data.dias,
            [dia]: [...data.dias[dia], { hora_inicio: '08:00', hora_fin: '16:00' }],
        });
    };

    const quitarBloque = (dia, indice) => {
        setData('dias', {
            ...data.dias,
            [dia]: data.dias[dia].filter((_, i) => i !== indice),
        });
    };

    const cambiarBloque = (dia, indice, campo, valor) => {
        setData('dias', {
            ...data.dias,
            [dia]: data.dias[dia].map((b, i) => (i === indice ? { ...b, [campo]: valor } : b)),
        });
    };

    const minutosPorDia = useMemo(() => {
        const totales = {};
        DIAS.forEach((d) => {
            totales[d.value] = data.dias[d.value].reduce((sum, b) => sum + minutosBloque(b), 0);
        });
        return totales;
    }, [data.dias]);

    const minutosSemana = useMemo(
        () => Object.values(minutosPorDia).reduce((sum, m) => sum + m, 0),
        [minutosPorDia],
    );

    const excedeSemana = minutosSemana > 40 * 60;

    const enviar = (e) => {
        e.preventDefault();

        transform((form) => {
            const bloquesPlanos = [];
            Object.entries(form.dias).forEach(([dia, lista]) => {
                lista.forEach((b) => {
                    bloquesPlanos.push({
                        dia_semana: Number(dia),
                        hora_inicio: b.hora_inicio,
                        hora_fin: b.hora_fin,
                    });
                });
            });

            const { dias, ...resto } = form;

            return { ...resto, bloques: bloquesPlanos };
        });

        submit(method, action, { preserveScroll: true });
    };

    return (
        <form onSubmit={enviar} className="space-y-6">
            {periodos && (
                <div className="max-w-sm">
                    <SelectInput
                        className="block w-full"
                        value={data.periodo_escolar_id}
                        onChange={(e) => {
                            setData('periodo_escolar_id', e.target.value);
                            onPeriodoChange?.(e.target.value);
                        }}
                    >
                        {periodos.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.nombre}
                            </option>
                        ))}
                    </SelectInput>
                </div>
            )}

            <p className="rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-400">
                El rango total de cada día (del inicio del primer bloque al fin del último) no puede
                exceder 8 horas (12 horas los sábados), y la suma de horas de toda la semana no puede
                exceder 40 horas. Puedes registrar varios bloques por día para turnos partidos.
            </p>

            <div
                className={`flex items-center justify-between rounded-md p-3 text-sm ${
                    excedeSemana
                        ? 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'
                        : 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                }`}
            >
                <span className="font-medium">Total semanal de disponibilidad</span>
                <span className="font-semibold">
                    {formatoHoras(minutosSemana)} / 40 horas
                    {excedeSemana ? ' — excede el límite' : ''}
                </span>
            </div>

            <InputError message={errors.bloques} className="mt-2" />

            <div className="space-y-4">
                {DIAS.map((dia) => (
                    <div key={dia.value} className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                        <div className="flex items-center justify-between">
                            <h4 className="font-medium text-slate-800 dark:text-slate-200">{dia.label}</h4>
                            <div className="flex items-center gap-3">
                                {minutosPorDia[dia.value] > 0 && (
                                    <span className="text-xs text-slate-400 dark:text-slate-500">
                                        {formatoHoras(minutosPorDia[dia.value])}h
                                    </span>
                                )}
                                <button
                                    type="button"
                                    onClick={() => agregarBloque(dia.value)}
                                    className="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    + Agregar bloque
                                </button>
                            </div>
                        </div>

                        {data.dias[dia.value].length === 0 && (
                            <p className="mt-2 text-sm text-slate-400 dark:text-slate-500">Sin disponibilidad.</p>
                        )}

                        <div className="mt-3 space-y-2">
                            {data.dias[dia.value].map((bloque, indice) => (
                                <div key={indice} className="flex items-center gap-3">
                                    <input
                                        type="time"
                                        min="07:00"
                                        max="21:00"
                                        step="3600"
                                        className="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:[color-scheme:dark]"
                                        value={bloque.hora_inicio}
                                        onChange={(e) => cambiarBloque(dia.value, indice, 'hora_inicio', e.target.value)}
                                    />
                                    <span className="text-slate-500 dark:text-slate-400">a</span>
                                    <input
                                        type="time"
                                        min="07:00"
                                        max="21:00"
                                        step="3600"
                                        className="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:[color-scheme:dark]"
                                        value={bloque.hora_fin}
                                        onChange={(e) => cambiarBloque(dia.value, indice, 'hora_fin', e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => quitarBloque(dia.value, indice)}
                                        className="text-sm text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Quitar
                                    </button>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>

            <PrimaryButton disabled={processing || excedeSemana}>Guardar disponibilidad</PrimaryButton>
        </form>
    );
}
