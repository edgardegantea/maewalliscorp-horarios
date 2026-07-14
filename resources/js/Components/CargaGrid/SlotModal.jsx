import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Minutos entre dos horas "HH:MM".
const aMinutos = (hora) => {
    const [h, m] = hora.split(':').map(Number);
    return h * 60 + m;
};

// Renderiza la opción de una asignatura en el selector, anotando cuántas
// horas de su cupo semanal quedan disponibles para el grupo seleccionado.
function opcionAsignatura(a, horasPorAsignatura) {
    const info = horasPorAsignatura[a.id];
    const agotada = Boolean(info && info.restantes <= 0);

    let etiqueta = a.nombre;
    if (a.semestre) {
        etiqueta += ` · sem. ${a.semestre}`;
    }
    if (a.horas_semana) {
        if (agotada) {
            etiqueta += ' — horas ya asignadas';
        } else if (info) {
            etiqueta += ` (quedan ${info.restantes}h de ${a.horas_semana}h)`;
        } else {
            etiqueta += ` (${a.horas_semana}h/semana)`;
        }
    }

    return (
        <option key={a.id} value={a.id} disabled={agotada}>
            {etiqueta}
        </option>
    );
}

export default function SlotModal({
    show,
    onClose,
    seleccion,
    contexto,
    asignaturas,
    grupos,
    aulas,
    onDuplicar,
}) {
    const cargaExistente = seleccion?.cargaExistente ?? null;
    const editando = Boolean(cargaExistente);
    const prellenado = seleccion?.prellenado ?? null;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        periodo_escolar_id: contexto.periodo.id,
        carrera_id: contexto.carrera.id,
        docente_id: contexto.docenteId,
        asignatura_id: '',
        grupo_ids: [],
        aula_id: '',
        dia_semana: seleccion?.dia_semana ?? '',
        hora_inicio: seleccion?.hora_inicio ?? '',
        hora_fin: seleccion?.hora_fin ?? '',
    });

    const [ocupados, setOcupados] = useState({ aulas: [], grupos: [] });
    const [verificacion, setVerificacion] = useState(null);
    const [horasInfo, setHorasInfo] = useState(null);
    const [horasPorAsignatura, setHorasPorAsignatura] = useState({});
    const debounce = useRef(null);
    const debounceHorasAsignaturas = useRef(null);

    // Al cambiar la selección de horas, resetea el formulario manteniendo el contexto
    // (o precarga los valores de la carga existente si se abrió en modo edición).
    useEffect(() => {
        if (seleccion) {
            reset('asignatura_id', 'grupo_ids', 'aula_id');
            setData((prev) => ({
                ...prev,
                dia_semana: seleccion.dia_semana,
                hora_inicio: seleccion.hora_inicio,
                hora_fin: seleccion.hora_fin,
                asignatura_id: cargaExistente?.asignatura_id ?? prellenado?.asignatura_id ?? '',
                grupo_ids: cargaExistente?.grupo_ids ?? prellenado?.grupo_ids ?? [],
                aula_id: cargaExistente?.aula_id ?? prellenado?.aula_id ?? '',
            }));
            setOcupados({ aulas: [], grupos: [] });
            setVerificacion(null);
            setHorasInfo(null);
            setHorasPorAsignatura({});
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [seleccion]);

    // Horas restantes por asignatura para los grupos seleccionados, para anotar
    // y deshabilitar opciones agotadas en el selector de asignatura.
    useEffect(() => {
        if (!show || !seleccion || data.grupo_ids.length === 0) {
            setHorasPorAsignatura({});
            return;
        }

        clearTimeout(debounceHorasAsignaturas.current);
        debounceHorasAsignaturas.current = setTimeout(() => {
            window.axios
                .post(route('admin.cargas.horas-asignaturas'), {
                    periodo_escolar_id: data.periodo_escolar_id,
                    asignatura_ids: asignaturas.map((a) => a.id),
                    grupo_ids: data.grupo_ids,
                    ignorar_carga_id: cargaExistente?.id ?? null,
                })
                .then((res) => setHorasPorAsignatura(res.data.horas));
        }, 250);

        return () => clearTimeout(debounceHorasAsignaturas.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, seleccion, data.grupo_ids]);

    // Dry-run de verificación cada vez que cambian aula/grupos/asignatura (con debounce).
    useEffect(() => {
        if (!show || !seleccion) {
            return;
        }

        clearTimeout(debounce.current);
        debounce.current = setTimeout(() => {
            window.axios
                .post(route('admin.cargas.verificar'), {
                    periodo_escolar_id: data.periodo_escolar_id,
                    docente_id: data.docente_id,
                    dia_semana: data.dia_semana,
                    hora_inicio: data.hora_inicio,
                    hora_fin: data.hora_fin,
                    aula_id: data.aula_id || null,
                    grupo_ids: data.grupo_ids,
                    asignatura_id: data.asignatura_id || null,
                    ignorar_carga_id: cargaExistente?.id ?? null,
                })
                .then((res) => {
                    setVerificacion(res.data.resultado);
                    setOcupados({
                        aulas: res.data.aulas_ocupadas,
                        grupos: res.data.grupos_ocupados,
                    });
                    setHorasInfo(res.data.horas);
                });
        }, 250);

        return () => clearTimeout(debounce.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, data.aula_id, data.grupo_ids, data.asignatura_id, data.hora_inicio, data.hora_fin, data.dia_semana]);

    const guardar = (e) => {
        e.preventDefault();
        if (editando) {
            put(route('admin.cargas.update', cargaExistente.id), {
                preserveScroll: true,
                onSuccess: () => onClose(true),
            });
            return;
        }

        // Si la asignatura tiene horas_semana definidas y aún quedarán horas por
        // asignar después de guardar este bloque, se conserva la selección
        // (asignatura, grupo(s) y aula) para que el admin siga asignando la misma
        // clase hasta agotar su cupo semanal.
        let continuar = null;
        if (horasInfo) {
            const duracionHoras = (aMinutos(data.hora_fin) - aMinutos(data.hora_inicio)) / 60;
            const restantesDespues = horasInfo.restantes - duracionHoras;
            if (restantesDespues > 0.01) {
                continuar = {
                    asignatura_id: data.asignatura_id,
                    grupo_ids: data.grupo_ids,
                    aula_id: data.aula_id,
                    motivo: 'continuar',
                    asignaturaNombre: asignaturas.find((a) => String(a.id) === String(data.asignatura_id))?.nombre ?? '',
                    horasRestantes: Math.round(restantesDespues * 100) / 100,
                };
            }
        }

        post(route('admin.cargas.store'), {
            preserveScroll: true,
            onSuccess: () => onClose(true, continuar),
        });
    };

    const eliminar = () => {
        if (confirm('¿Eliminar esta carga académica?')) {
            router.delete(route('admin.cargas.destroy', cargaExistente.id), {
                preserveScroll: true,
                onSuccess: () => onClose(true),
            });
        }
    };

    const duplicar = () => {
        onDuplicar?.({
            asignatura_id: data.asignatura_id,
            grupo_ids: data.grupo_ids,
            aula_id: data.aula_id,
        });
    };

    const alternarGrupo = (grupoId, marcado) => {
        setData(
            'grupo_ids',
            marcado ? [...data.grupo_ids, grupoId] : data.grupo_ids.filter((id) => id !== grupoId),
        );
    };

    const gruposSeleccionados = useMemo(
        () => grupos.filter((g) => data.grupo_ids.includes(g.id)),
        [grupos, data.grupo_ids],
    );
    const matriculaTotal = gruposSeleccionados.reduce((sum, g) => sum + (g.matricula ?? 0), 0);
    const aulaSeleccionada = useMemo(
        () => aulas.find((a) => String(a.id) === String(data.aula_id)),
        [aulas, data.aula_id],
    );
    const excedeCapacidad =
        matriculaTotal > 0 && aulaSeleccionada?.capacidad ? matriculaTotal > aulaSeleccionada.capacidad : false;

    const asignaturaSeleccionada = useMemo(
        () => asignaturas.find((a) => String(a.id) === String(data.asignatura_id)),
        [asignaturas, data.asignatura_id],
    );
    const gruposDeOtroSemestre = useMemo(() => {
        if (!asignaturaSeleccionada?.semestre) {
            return [];
        }
        return gruposSeleccionados.filter((g) => g.semestre && g.semestre !== asignaturaSeleccionada.semestre);
    }, [asignaturaSeleccionada, gruposSeleccionados]);

    if (!seleccion) {
        return null;
    }

    const mensajesVerificacion = verificacion?.mensajes ?? [];

    return (
        <Modal show={show} onClose={() => onClose(false)}>
            <form onSubmit={guardar} className="p-6">
                <h3 className="text-lg font-medium text-slate-900 dark:text-white">
                    {editando ? 'Editar asignación' : 'Nueva asignación'}
                </h3>
                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {DIAS[seleccion.dia_semana]} · {seleccion.hora_inicio} a {seleccion.hora_fin}
                </p>

                {prellenado && (
                    <div className="mt-3 rounded-md bg-indigo-50 p-3 text-sm text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                        {prellenado.motivo === 'grupo'
                            ? 'Grupo preseleccionado. Elige asignatura y aula para completar la asignación.'
                            : 'Duplicando asignatura, grupo(s) y aula de otra clase. Ajusta lo que necesites y guarda.'}
                    </div>
                )}

                {mensajesVerificacion.length > 0 && (
                    <div className="mt-3 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400">
                        <ul className="list-disc space-y-1 pl-4">
                            {mensajesVerificacion.map((mensaje) => (
                                <li key={mensaje}>{mensaje}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {excedeCapacidad && (
                    <div className="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-400">
                        Los grupos seleccionados suman {matriculaTotal} alumnos, pero el aula solo tiene capacidad
                        para {aulaSeleccionada.capacidad}. Puedes continuar, pero verifica que sea correcto.
                    </div>
                )}

                {gruposDeOtroSemestre.length > 0 && (
                    <div className="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-400">
                        "{asignaturaSeleccionada.nombre}" es de semestre {asignaturaSeleccionada.semestre}, pero{' '}
                        {gruposDeOtroSemestre.map((g) => g.nombre).join(', ')} {gruposDeOtroSemestre.length === 1 ? 'es' : 'son'} de otro
                        semestre. Puedes continuar, pero verifica que sea correcto.
                    </div>
                )}

                <div className="mt-4 space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Asignatura</label>
                        <SelectInput
                            className="mt-1 block w-full"
                            value={data.asignatura_id}
                            onChange={(e) => setData('asignatura_id', e.target.value)}
                        >
                            <option value="">Selecciona una asignatura</option>
                            {data.dia_semana === 6 ? (
                                <>
                                    <optgroup label="MÓDULO 1">
                                        {asignaturas.filter((a) => Number(a.modulo_sabatino) === 1).map((a) => opcionAsignatura(a, horasPorAsignatura))}
                                    </optgroup>
                                    <optgroup label="MÓDULO 2">
                                        {asignaturas.filter((a) => Number(a.modulo_sabatino) === 2).map((a) => opcionAsignatura(a, horasPorAsignatura))}
                                    </optgroup>
                                    {asignaturas.some((a) => !a.modulo_sabatino) && (
                                        <optgroup label="Sin módulo asignado">
                                            {asignaturas.filter((a) => !a.modulo_sabatino).map((a) => opcionAsignatura(a, horasPorAsignatura))}
                                        </optgroup>
                                    )}
                                </>
                            ) : (
                                asignaturas.map((a) => opcionAsignatura(a, horasPorAsignatura))
                            )}
                        </SelectInput>
                        {errors.asignatura_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.asignatura_id}</p>}
                        {horasInfo && (
                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                {horasInfo.asignadas}h de {horasInfo.horas_semana}h asignadas a este grupo · quedan {horasInfo.restantes}h
                            </p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">
                            Grupo(s)
                        </label>
                        <p className="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                            Selecciona uno o varios grupos si la clase se imparte a una combinación de grupos.
                            {data.dia_semana === 6 && ' Los sábados solo se puede asignar a grupos sabatinos (terminados en "F", p. ej. 1F).'}
                        </p>
                        <div className="mt-1 max-h-40 space-y-1 overflow-y-auto rounded-md border border-slate-300 p-2 dark:border-slate-700">
                            {grupos.map((g) => {
                                const noEsSabatino = data.dia_semana === 6 && !/f$/i.test(g.nombre.trim());
                                const ocupado = (ocupados.grupos.includes(g.id) && !data.grupo_ids.includes(g.id)) || noEsSabatino;
                                const marcado = data.grupo_ids.includes(g.id);
                                return (
                                    <label
                                        key={g.id}
                                        className={`flex items-center gap-2 rounded px-1.5 py-1 text-sm ${
                                            ocupado
                                                ? 'cursor-not-allowed text-slate-400 dark:text-slate-600'
                                                : 'cursor-pointer text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                                        }`}
                                    >
                                        <input
                                            type="checkbox"
                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                                            checked={marcado}
                                            disabled={ocupado}
                                            onChange={(e) => alternarGrupo(g.id, e.target.checked)}
                                        />
                                        {g.nombre}
                                        {g.carrera_nombre ? ` · ${g.carrera_nombre}` : ''} ({g.matricula} alumnos)
                                        {g.hora_inicio && g.hora_fin
                                            ? ` · ${g.hora_inicio.slice(0, 5)}-${g.hora_fin.slice(0, 5)}`
                                            : ''}
                                        {noEsSabatino ? ' — no es grupo sabatino' : ocupado ? ' — ocupado' : ''}
                                    </label>
                                );
                            })}
                        </div>
                        {errors.grupo_ids && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.grupo_ids}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Aula</label>
                        <SelectInput
                            className="mt-1 block w-full"
                            value={data.aula_id}
                            onChange={(e) => setData('aula_id', e.target.value)}
                        >
                            <option value="">Selecciona un aula</option>
                            {aulas.map((a) => {
                                const ocupado = ocupados.aulas.includes(a.id);
                                return (
                                    <option key={a.id} value={a.id} disabled={ocupado}>
                                        {a.nombre}
                                        {a.capacidad ? ` (cap. ${a.capacidad})` : ''}
                                        {ocupado ? ' — ocupada' : ''}
                                    </option>
                                );
                            })}
                        </SelectInput>
                        {errors.aula_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.aula_id}</p>}
                    </div>
                </div>

                {errors.horario && (
                    <div className="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-400">
                        {Array.isArray(errors.horario) ? errors.horario.join(' ') : errors.horario}
                    </div>
                )}

                <div className="mt-6 flex items-center justify-between gap-3">
                    {editando ? (
                        <div className="flex gap-4">
                            <button
                                type="button"
                                onClick={eliminar}
                                className="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            >
                                Eliminar
                            </button>
                            <button
                                type="button"
                                onClick={duplicar}
                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                Duplicar…
                            </button>
                        </div>
                    ) : (
                        <span />
                    )}
                    <div className="flex gap-3">
                        <SecondaryButton type="button" onClick={() => onClose(false)}>
                            Cancelar
                        </SecondaryButton>
                        <PrimaryButton
                            disabled={
                                processing ||
                                !data.asignatura_id ||
                                data.grupo_ids.length === 0 ||
                                !data.aula_id ||
                                (verificacion && !verificacion.valido)
                            }
                        >
                            {editando ? 'Guardar cambios' : 'Guardar carga'}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    );
}
