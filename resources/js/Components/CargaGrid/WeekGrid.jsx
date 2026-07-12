import { useEffect, useState } from 'react';
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

// Suma una hora a "HH:00" -> "HH+1:00".
const siguienteHora = (hora) => {
    const h = parseInt(hora.slice(0, 2), 10) + 1;
    return `${String(h).padStart(2, '0')}:00`;
};

export default function WeekGrid({ dias, slots, onSeleccion, onClickReservado }) {
    const [arrastre, setArrastre] = useState(null); // { dia, desde, hasta } (índices de slot)

    // Índice rápido: dias[diaSemana][hora] -> celda
    const mapa = {};
    dias.forEach((d) => {
        mapa[d.dia_semana] = {};
        d.horas.forEach((h, idx) => {
            mapa[d.dia_semana][idx] = h;
        });
    });

    const celdaEn = (dia, idx) => mapa[dia]?.[idx];

    const iniciar = (celda, dia, idx) => {
        if (celda.estado === 'reservado_actual') {
            onClickReservado?.(celda);
            return;
        }
        if (celda.estado !== 'disponible') {
            return;
        }
        setArrastre({ dia, desde: idx, hasta: idx });
    };

    const extender = (dia, idx) => {
        if (!arrastre || arrastre.dia !== dia) {
            return;
        }
        // Solo extiende a través de celdas contiguas disponibles.
        const paso = idx > arrastre.desde ? 1 : -1;
        let limite = arrastre.desde;
        for (let i = arrastre.desde; i !== idx + paso; i += paso) {
            const c = celdaEn(dia, i);
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

    const estaSeleccionada = (dia, idx) => {
        if (!arrastre || arrastre.dia !== dia) {
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
                    {DIAS.map((d) => (
                        <col key={d.value} />
                    ))}
                </colgroup>
                <thead>
                    <tr>
                        <th className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                            Hora
                        </th>
                        {DIAS.map((d) => (
                            <th
                                key={d.value}
                                className="border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >
                                {d.label}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {slots.map((hora, idx) => (
                        <tr key={hora}>
                            <td className="border border-slate-200 bg-slate-50 px-2 py-1 text-center text-[11px] text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                                {hora}–{siguienteHora(hora)}
                            </td>
                            {DIAS.map((d) => {
                                const celda = celdaEn(d.value, idx) ?? { estado: 'fuera_disponibilidad', hora };
                                return (
                                    <Cell
                                        key={`${d.value}-${hora}`}
                                        celda={celda}
                                        seleccionada={estaSeleccionada(d.value, idx)}
                                        onMouseDown={(c) => iniciar(c, d.value, idx)}
                                        onMouseEnter={() => extender(d.value, idx)}
                                    />
                                );
                            })}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
