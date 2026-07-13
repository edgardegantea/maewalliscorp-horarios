import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';

const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

export default function SlotModal({
    show,
    onClose,
    seleccion,
    contexto,
    asignaturas,
    grupos,
    aulas,
}) {
    const cargaExistente = seleccion?.cargaExistente ?? null;
    const editando = Boolean(cargaExistente);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        periodo_escolar_id: contexto.periodo.id,
        carrera_id: contexto.carrera.id,
        docente_id: contexto.docenteId,
        asignatura_id: '',
        grupo_id: '',
        aula_id: '',
        dia_semana: seleccion?.dia_semana ?? '',
        hora_inicio: seleccion?.hora_inicio ?? '',
        hora_fin: seleccion?.hora_fin ?? '',
    });

    const [ocupados, setOcupados] = useState({ aulas: [], grupos: [] });
    const [verificacion, setVerificacion] = useState(null);
    const debounce = useRef(null);

    // Al cambiar la selección de horas, resetea el formulario manteniendo el contexto
    // (o precarga los valores de la carga existente si se abrió en modo edición).
    useEffect(() => {
        if (seleccion) {
            reset('asignatura_id', 'grupo_id', 'aula_id');
            setData((prev) => ({
                ...prev,
                dia_semana: seleccion.dia_semana,
                hora_inicio: seleccion.hora_inicio,
                hora_fin: seleccion.hora_fin,
                asignatura_id: cargaExistente?.asignatura_id ?? '',
                grupo_id: cargaExistente?.grupo_id ?? '',
                aula_id: cargaExistente?.aula_id ?? '',
            }));
            setOcupados({ aulas: [], grupos: [] });
            setVerificacion(null);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [seleccion]);

    // Dry-run de verificación cada vez que cambian aula/grupo/asignatura (con debounce).
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
                    grupo_id: data.grupo_id || null,
                    asignatura_id: data.asignatura_id || null,
                    ignorar_carga_id: cargaExistente?.id ?? null,
                })
                .then((res) => {
                    setVerificacion(res.data.resultado);
                    setOcupados({
                        aulas: res.data.aulas_ocupadas,
                        grupos: res.data.grupos_ocupados,
                    });
                });
        }, 250);

        return () => clearTimeout(debounce.current);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [show, data.aula_id, data.grupo_id, data.asignatura_id, data.hora_inicio, data.hora_fin, data.dia_semana]);

    const guardar = (e) => {
        e.preventDefault();
        if (editando) {
            put(route('admin.cargas.update', cargaExistente.id), {
                preserveScroll: true,
                onSuccess: () => onClose(true),
            });
            return;
        }
        post(route('admin.cargas.store'), {
            preserveScroll: true,
            onSuccess: () => onClose(true),
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

    const grupoSeleccionado = useMemo(
        () => grupos.find((g) => String(g.id) === String(data.grupo_id)),
        [grupos, data.grupo_id],
    );
    const aulaSeleccionada = useMemo(
        () => aulas.find((a) => String(a.id) === String(data.aula_id)),
        [aulas, data.aula_id],
    );
    const excedeCapacidad =
        grupoSeleccionado?.matricula && aulaSeleccionada?.capacidad
            ? grupoSeleccionado.matricula > aulaSeleccionada.capacidad
            : false;

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
                        El grupo tiene {grupoSeleccionado.matricula} alumnos, pero el aula solo tiene capacidad
                        para {aulaSeleccionada.capacidad}. Puedes continuar, pero verifica que sea correcto.
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
                            {asignaturas.map((a) => (
                                <option key={a.id} value={a.id}>
                                    {a.nombre}
                                    {a.horas_semana ? ` (${a.horas_semana}h/semana)` : ''}
                                </option>
                            ))}
                        </SelectInput>
                        {errors.asignatura_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.asignatura_id}</p>}
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Grupo</label>
                        <SelectInput
                            className="mt-1 block w-full"
                            value={data.grupo_id}
                            onChange={(e) => setData('grupo_id', e.target.value)}
                        >
                            <option value="">Selecciona un grupo</option>
                            {grupos.map((g) => {
                                const ocupado = ocupados.grupos.includes(g.id);
                                return (
                                    <option key={g.id} value={g.id} disabled={ocupado}>
                                        {g.nombre} ({g.matricula} alumnos){ocupado ? ' — ocupado' : ''}
                                    </option>
                                );
                            })}
                        </SelectInput>
                        {errors.grupo_id && <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.grupo_id}</p>}
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
                        <button
                            type="button"
                            onClick={eliminar}
                            className="text-sm font-medium text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Eliminar
                        </button>
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
                                !data.grupo_id ||
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
