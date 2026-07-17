import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import { useForm } from '@inertiajs/react';
import { useMemo } from 'react';

// Cada grupo es un día (o, en sábado, un módulo dentro del día) con su propia
// lista independiente de bloques. El sábado se separa en dos porque el
// módulo 1 y el módulo 2 son semanas distintas del semestre y pueden tener
// horarios de reloj totalmente distintos sin que sea un conflicto real.
const GRUPOS = [
    { clave: '1', dia: 1, modulo: null, label: 'Lunes' },
    { clave: '2', dia: 2, modulo: null, label: 'Martes' },
    { clave: '3', dia: 3, modulo: null, label: 'Miércoles' },
    { clave: '4', dia: 4, modulo: null, label: 'Jueves' },
    { clave: '5', dia: 5, modulo: null, label: 'Viernes' },
    { clave: '6-1', dia: 6, modulo: 1, label: 'Sábado · Módulo 1' },
    { clave: '6-2', dia: 6, modulo: 2, label: 'Sábado · Módulo 2' },
    { clave: '7', dia: 7, modulo: null, label: 'Domingo' },
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

function claveDe(bloque) {
    return bloque.dia_semana === 6 ? `6-${bloque.modulo_sabatino ?? 1}` : `${bloque.dia_semana}`;
}

function agrupar(bloques) {
    const porGrupo = {};
    GRUPOS.forEach((g) => (porGrupo[g.clave] = []));
    bloques.forEach((b) => {
        const clave = claveDe(b);
        if (porGrupo[clave]) {
            porGrupo[clave].push({ hora_inicio: hhmm(b.hora_inicio), hora_fin: hhmm(b.hora_fin) });
        }
    });
    return porGrupo;
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
        grupos: inicial,
        ...extraFields,
    });

    const agregarBloque = (clave) => {
        setData('grupos', {
            ...data.grupos,
            [clave]: [...data.grupos[clave], { hora_inicio: '08:00', hora_fin: '16:00' }],
        });
    };

    const quitarBloque = (clave, indice) => {
        setData('grupos', {
            ...data.grupos,
            [clave]: data.grupos[clave].filter((_, i) => i !== indice),
        });
    };

    const cambiarBloque = (clave, indice, campo, valor) => {
        setData('grupos', {
            ...data.grupos,
            [clave]: data.grupos[clave].map((b, i) => (i === indice ? { ...b, [campo]: valor } : b)),
        });
    };

    const minutosPorGrupo = useMemo(() => {
        const totales = {};
        GRUPOS.forEach((g) => {
            totales[g.clave] = data.grupos[g.clave].reduce((sum, b) => sum + minutosBloque(b), 0);
        });
        return totales;
    }, [data.grupos]);

    // El sábado cuenta una sola vez en el total semanal (el módulo con más
    // horas): módulo 1 y módulo 2 nunca coinciden en el calendario real.
    const minutosSemana = useMemo(() => {
        const baseSemana = GRUPOS.filter((g) => g.dia !== 6).reduce((sum, g) => sum + minutosPorGrupo[g.clave], 0);
        const sabado = Math.max(minutosPorGrupo['6-1'] ?? 0, minutosPorGrupo['6-2'] ?? 0);
        return baseSemana + sabado;
    }, [minutosPorGrupo]);

    const excedeSemana = minutosSemana > 40 * 60;

    // El módulo 1 y el módulo 2 del sábado deben sumar exactamente las mismas
    // horas (el horario de reloj sí puede ser independiente entre ambos).
    const minutosModulo1 = minutosPorGrupo['6-1'] ?? 0;
    const minutosModulo2 = minutosPorGrupo['6-2'] ?? 0;
    const desbalanceSabado = minutosModulo1 !== minutosModulo2;

    const enviar = (e) => {
        e.preventDefault();

        transform((form) => {
            const bloquesPlanos = [];
            GRUPOS.forEach((g) => {
                (form.grupos[g.clave] ?? []).forEach((b) => {
                    bloquesPlanos.push({
                        dia_semana: g.dia,
                        modulo_sabatino: g.modulo,
                        hora_inicio: b.hora_inicio,
                        hora_fin: b.hora_fin,
                    });
                });
            });

            const { grupos, ...resto } = form;

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
                La suma de horas de cada día (o, en sábado, de cada módulo) no puede exceder 8 horas (12
                los sábados), y la suma de la semana no puede exceder 40 horas (el sábado cuenta una sola
                vez, con el módulo de más horas). Puedes registrar varios bloques por día para turnos
                partidos. El módulo 1 y el módulo 2 del sábado pueden tener horarios de reloj
                completamente distintos (ocurren en semanas distintas del semestre y no chocan entre sí),
                pero deben sumar exactamente la misma cantidad de horas entre los dos.
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

            {desbalanceSabado && (
                <div className="flex items-center justify-between rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400">
                    <span className="font-medium">Módulo 1 y módulo 2 del sábado deben sumar las mismas horas</span>
                    <span className="font-semibold">
                        Módulo 1: {formatoHoras(minutosModulo1)}h · Módulo 2: {formatoHoras(minutosModulo2)}h
                    </span>
                </div>
            )}

            <InputError message={errors.bloques} className="mt-2" />

            <div className="space-y-4">
                {GRUPOS.map((grupo) => (
                    <div key={grupo.clave} className="rounded-lg border border-slate-200 p-4 dark:border-slate-700">
                        <div className="flex items-center justify-between">
                            <h4 className="font-medium text-slate-800 dark:text-slate-200">{grupo.label}</h4>
                            <div className="flex items-center gap-3">
                                {minutosPorGrupo[grupo.clave] > 0 && (
                                    <span className="text-xs text-slate-400 dark:text-slate-500">
                                        {formatoHoras(minutosPorGrupo[grupo.clave])}h
                                    </span>
                                )}
                                <button
                                    type="button"
                                    onClick={() => agregarBloque(grupo.clave)}
                                    className="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                >
                                    + Agregar bloque
                                </button>
                            </div>
                        </div>

                        {data.grupos[grupo.clave].length === 0 && (
                            <p className="mt-2 text-sm text-slate-400 dark:text-slate-500">Sin disponibilidad.</p>
                        )}

                        <div className="mt-3 space-y-2">
                            {data.grupos[grupo.clave].map((bloque, indice) => (
                                <div key={indice} className="flex items-center gap-3">
                                    <input
                                        type="time"
                                        min="07:00"
                                        max="21:00"
                                        step="3600"
                                        className="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:[color-scheme:dark]"
                                        value={bloque.hora_inicio}
                                        onChange={(e) => cambiarBloque(grupo.clave, indice, 'hora_inicio', e.target.value)}
                                    />
                                    <span className="text-slate-500 dark:text-slate-400">a</span>
                                    <input
                                        type="time"
                                        min="07:00"
                                        max="21:00"
                                        step="3600"
                                        className="rounded-md border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:[color-scheme:dark]"
                                        value={bloque.hora_fin}
                                        onChange={(e) => cambiarBloque(grupo.clave, indice, 'hora_fin', e.target.value)}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => quitarBloque(grupo.clave, indice)}
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

            <PrimaryButton disabled={processing || excedeSemana || desbalanceSabado}>Guardar disponibilidad</PrimaryButton>
        </form>
    );
}
