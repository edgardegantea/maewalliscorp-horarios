const ESTILOS = {
    disponible: 'bg-white hover:bg-indigo-50 cursor-pointer dark:bg-slate-900 dark:hover:bg-indigo-500/10',
    fuera_disponibilidad:
        'bg-slate-100 bg-[repeating-linear-gradient(45deg,transparent,transparent_4px,rgba(0,0,0,0.04)_4px,rgba(0,0,0,0.04)_8px)] cursor-not-allowed dark:bg-slate-800 dark:bg-[repeating-linear-gradient(45deg,transparent,transparent_4px,rgba(255,255,255,0.04)_4px,rgba(255,255,255,0.04)_8px)]',
    reservado_actual: 'bg-indigo-100 hover:bg-indigo-200 cursor-pointer dark:bg-indigo-500/20 dark:hover:bg-indigo-500/30',
    reservado_otro: 'bg-amber-100 cursor-not-allowed dark:bg-amber-500/20',
    grupo_ocupado: 'bg-rose-100 cursor-not-allowed dark:bg-rose-500/20',
    grupo_fuera_horario:
        'bg-slate-100 bg-[repeating-linear-gradient(-45deg,transparent,transparent_4px,rgba(0,0,0,0.05)_4px,rgba(0,0,0,0.05)_8px)] cursor-not-allowed dark:bg-slate-800 dark:bg-[repeating-linear-gradient(-45deg,transparent,transparent_4px,rgba(255,255,255,0.05)_4px,rgba(255,255,255,0.05)_8px)]',
};

export default function Cell({ celda, seleccionada, onMouseDown, onMouseEnter }) {
    const clases = ESTILOS[celda.estado] ?? 'bg-white dark:bg-slate-900';
    const seleccionClase = seleccionada
        ? 'ring-2 ring-inset ring-indigo-500 bg-indigo-200 dark:bg-indigo-500/40'
        : '';

    return (
        <td
            onMouseDown={() => onMouseDown(celda)}
            onMouseEnter={() => onMouseEnter(celda)}
            title={celda.estado === 'grupo_fuera_horario' ? 'Fuera del horario del grupo' : undefined}
            className={`h-12 select-none border border-slate-200 px-1 text-center align-middle text-[11px] leading-tight dark:border-slate-700 ${clases} ${seleccionClase}`}
        >
            {(celda.estado === 'reservado_actual' || celda.estado === 'reservado_otro') && (
                <div className="truncate">
                    <div className="font-medium text-slate-800 dark:text-slate-100">{celda.asignatura}</div>
                    <div className="text-slate-500 dark:text-slate-400">
                        {celda.grupo} · {celda.aula}
                    </div>
                </div>
            )}
            {celda.estado === 'grupo_ocupado' && (
                <div className="truncate">
                    <div className="font-medium text-slate-800 dark:text-slate-100">{celda.asignatura}</div>
                    <div className="text-slate-500 dark:text-slate-400">{celda.docente}</div>
                </div>
            )}
        </td>
    );
}
