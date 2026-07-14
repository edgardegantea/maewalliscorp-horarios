import { Fragment, useEffect, useState } from 'react';
import Cell from './Cell';

const DIAS = [
    { value: 1, label: 'Lun' },
    { value: 2, label: 'Mar' },
    { value: 3, label: 'Mié' },
    { value: 4, label: 'Jue' },
    { value: 5, label: 'Vie' },
    { value: 6, label: 'Sáb' },
    { value: 7, label: 'Dom' },
];

const DIA_SABADO = 6;

// Suma una hora a "HH:00" -> "HH+1:00".
const siguienteHora = (hora) => {
    const h = parseInt(hora.slice(0, 2), 10) + 1;
    return `${String(h).padStart(2, '0')}:00`;
};

export default function WeekGrid({ dias, slots, onSeleccion, onClickReservado }) {
    // arrastre: { dia, modulo, desde, hasta } (índices de slot). `modulo` es
    // 1 o 2 solo para el sábado, que se muestra en dos columnas paralelas.
    const [arrastre, setArrastre] = useState(null);

    // Índice rápido: dias[diaSemana][modulo][hora] -> celda
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

    const celdaEn = (dia, modulo, idx) => mapa[dia]?.[modulo]?.[idx];

    const iniciar = (celda, dia, modulo, idx) => {
        if (celda.estado === 'reservado_actual') {
            onClickReservado?.(celda);
            return;
        }
        if (celda.estado !== 'disponible') {
            return;
        }
        setArrastre({ dia, modulo, desde: idx, hasta: idx });
    };

    const extender = (dia, modulo, idx) => {
        if (!arrastre || arrastre.dia !== dia || arrastre.modulo !== modulo) {
            return;
        }
        // Solo extiende a través de celdas contiguas disponibles.
        const paso = idx > arrastre.desde ? 1 : -1;
        let limite = arrastre.desde;
        for (let i = arrastre.desde; i !== idx + paso; i += paso) {
            const c = celdaEn(dia, modulo, i);
            if (!c || c.estado !== 'disponible') {
                break;
            }
            limite = i;
        }
        setArrastre({ ...arrastre, hasta: limite });
    };

    useEffect(() => {
        const soltar = () => {
            setArrastre((actual) => {
                if (actual) {
                    const desde = Math.min(actual.desde, actual.hasta);
                    const hasta = Math.max(actual.desde, actual.hasta);
                    onSeleccion({
                        dia_semana: actual.dia,
                        modulo_sabatino: actual.dia === DIA_SABADO ? actual.modulo : null,
                        hora_inicio: slots[desde],
                        hora_fin: siguienteHora(slots[hasta]),
                    });
                }
                return null;
            });
        };

        window.addEventListener('mouseup', soltar);
        return () => window.removeEventListener('mouseup', soltar);
    }, [onSeleccion, slots]);

    const estaSeleccionada = (dia, modulo, idx) => {
        if (!arrastre || arrastre.dia !== dia || arrastre.modulo !== modulo) {
            return false;
        }
        const desde = Math.min(arrastre.desde, arrastre.hasta);
        const hasta = Math.max(arrastre.desde, arrastre.hasta);
        return idx >= desde && idx <= hasta;
    };

    return (
        <div className="overflow-x-auto">
            <table className="w-full table-fixed border-collapse">
                <colgroup>
                    <col className="w-28" />
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
                        <th
                            rowSpan={2}
                            className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                        >
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
                            {DIAS.map((d) => {
                                if (d.value !== DIA_SABADO) {
                                    const celda = celdaEn(d.value, 1, idx) ?? { estado: 'fuera_disponibilidad', hora };
                                    return (
                                        <Cell
                                            key={`${d.value}-${hora}`}
                                            celda={celda}
                                            seleccionada={estaSeleccionada(d.value, 1, idx)}
                                            onMouseDown={(c) => iniciar(c, d.value, 1, idx)}
                                            onMouseEnter={() => extender(d.value, 1, idx)}
                                        />
                                    );
                                }

                                const celdaM1 = celdaEn(d.value, 1, idx) ?? { estado: 'fuera_disponibilidad', hora };
                                const celdaM2 = celdaEn(d.value, 2, idx) ?? { estado: 'fuera_disponibilidad', hora };

                                return (
                                    <Fragment key={`${d.value}-${hora}`}>
                                        <Cell
                                            celda={celdaM1}
                                            seleccionada={estaSeleccionada(d.value, 1, idx)}
                                            onMouseDown={(c) => iniciar(c, d.value, 1, idx)}
                                            onMouseEnter={() => extender(d.value, 1, idx)}
                                        />
                                        <Cell
                                            celda={celdaM2}
                                            seleccionada={estaSeleccionada(d.value, 2, idx)}
                                            onMouseDown={(c) => iniciar(c, d.value, 2, idx)}
                                            onMouseEnter={() => extender(d.value, 2, idx)}
                                        />
                                    </Fragment>
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
