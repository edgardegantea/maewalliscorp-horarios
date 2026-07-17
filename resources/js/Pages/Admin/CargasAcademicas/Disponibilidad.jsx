import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import WeekGrid from '@/Components/CargaGrid/WeekGrid';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Superpone las propuestas de un docente sobre su grid real (disponibilidad +
// ocupación ya calculadas por el backend): cualquier celda "disponible" que
// coincida con una propuesta pasa a un estado "propuesta" clicable.
function conPropuestasSuperpuestas(dias, propuestasDelDocente) {
    const indice = {};
    propuestasDelDocente.forEach((p) => {
        indice[`${p.dia_semana}-${p.hora_inicio}-${p.modulo_sabatino ?? 1}`] = p;
    });

    const superponer = (modulo) => (h, d) => {
        if (h.estado !== 'disponible') {
            return h;
        }
        const propuesta = indice[`${d.dia_semana}-${h.hora}-${modulo}`];
        return propuesta ? { ...h, estado: 'propuesta', aula: propuesta.aula_nombre, propuesta } : h;
    };

    return dias.map((d) => ({
        ...d,
        horas: d.horas.map((h) => superponer(1)(h, d)),
        horas_modulo2: d.horas_modulo2 ? d.horas_modulo2.map((h) => superponer(2)(h, d)) : d.horas_modulo2,
    }));
}

export default function Disponibilidad({ periodo, grupoPreseleccionadoId, grupos, asignaturas, slots }) {
    const [grupoIds, setGrupoIds] = useState(grupoPreseleccionadoId ? [grupoPreseleccionadoId] : []);
    const [asignaturaId, setAsignaturaId] = useState('');
    const [docenteFiltroId, setDocenteFiltroId] = useState('');
    const [docentesFiltro, setDocentesFiltro] = useState([]);
    const [propuestas, setPropuestas] = useState(null);
    const [buscando, setBuscando] = useState(false);
    const [docenteActivoId, setDocenteActivoId] = useState(null);
    const [dias, setDias] = useState(null);
    const [cargandoGrid, setCargandoGrid] = useState(false);
    const [asignando, setAsignando] = useState(false);
    const [mensaje, setMensaje] = useState(null);

    const gruposSeleccionados = useMemo(() => grupos.filter((g) => grupoIds.includes(g.id)), [grupos, grupoIds]);
    const carreraId = gruposSeleccionados[0]?.carrera_id;

    useEffect(() => {
        if (grupoIds.length === 0) {
            setDocentesFiltro([]);
            setDocenteFiltroId('');
            return;
        }

        window.axios
            .post(route('admin.cargas.disponibilidad.docentes'), {
                periodo_escolar_id: periodo.id,
                grupo_ids: grupoIds,
            })
            .then((res) => setDocentesFiltro(res.data.docentes));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [grupoIds.join(',')]);

    // Docentes con al menos una propuesta, para las pestañas del grid.
    const docentesConPropuestas = useMemo(() => {
        if (!propuestas) {
            return [];
        }
        const porId = new Map();
        propuestas.forEach((p) => {
            if (!porId.has(p.docente_id)) {
                porId.set(p.docente_id, { id: p.docente_id, nombre: p.docente_nombre, total: 0 });
            }
            porId.get(p.docente_id).total += 1;
        });
        return Array.from(porId.values()).sort((a, b) => b.total - a.total);
    }, [propuestas]);

    const cargarGrid = (docenteId) => {
        if (!docenteId || !carreraId) {
            setDias(null);
            return;
        }
        setCargandoGrid(true);
        window.axios
            .get(route('admin.cargas.grid-data'), {
                params: { periodo: periodo.id, carrera: carreraId, docente: docenteId, grupo: grupoIds[0] },
            })
            .then((res) => setDias(res.data.dias))
            .finally(() => setCargandoGrid(false));
    };

    useEffect(() => {
        if (docenteActivoId) {
            cargarGrid(docenteActivoId);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [docenteActivoId]);

    const buscar = () => {
        if (grupoIds.length === 0) {
            return;
        }

        setBuscando(true);
        setMensaje(null);
        setPropuestas(null);
        setDias(null);
        setDocenteActivoId(null);
        window.axios
            .post(route('admin.cargas.disponibilidad.buscar'), {
                periodo_escolar_id: periodo.id,
                grupo_ids: grupoIds,
                asignatura_id: asignaturaId || null,
                docente_ids: docenteFiltroId ? [docenteFiltroId] : null,
            })
            .then((res) => {
                setPropuestas(res.data.propuestas);
                if (res.data.propuestas.length > 0) {
                    setDocenteActivoId(res.data.propuestas[0].docente_id);
                }
            })
            .finally(() => setBuscando(false));
    };

    const asignar = (propuesta) => {
        if (asignando) {
            return;
        }
        if (!asignaturaId) {
            setMensaje({ tipo: 'error', texto: 'Selecciona una asignatura antes de asignar.' });
            return;
        }

        setAsignando(true);
        setMensaje(null);

        window.axios
            .post(route('admin.cargas.store'), {
                periodo_escolar_id: periodo.id,
                carrera_id: carreraId,
                docente_id: propuesta.docente_id,
                asignatura_id: asignaturaId,
                grupo_ids: grupoIds,
                aula_id: propuesta.aula_id,
                dia_semana: propuesta.dia_semana,
                hora_inicio: propuesta.hora_inicio,
                hora_fin: propuesta.hora_fin,
                modulo_sabatino: propuesta.modulo_sabatino,
            })
            .then(() => {
                setMensaje({
                    tipo: 'ok',
                    texto: `Clase asignada: ${DIAS[propuesta.dia_semana]} ${propuesta.hora_inicio}-${propuesta.hora_fin} con ${propuesta.docente_nombre}.`,
                });
                setPropuestas((prev) => prev?.filter((p) => p !== propuesta) ?? null);
                cargarGrid(propuesta.docente_id);
            })
            .catch((err) => {
                const primerError = Object.values(err.response?.data?.errors ?? {})[0]?.[0];
                setMensaje({ tipo: 'error', texto: primerError ?? 'No se pudo guardar la asignación.' });
            })
            .finally(() => setAsignando(false));
    };

    const diasConPropuestas = useMemo(() => {
        if (!dias || !propuestas || !docenteActivoId) {
            return dias;
        }
        return conPropuestasSuperpuestas(
            dias,
            propuestas.filter((p) => p.docente_id === docenteActivoId),
        );
    }, [dias, propuestas, docenteActivoId]);

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Buscar disponibilidad</h2>}>
            <Head title="Buscar disponibilidad" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Cargas académicas', href: route('admin.cargas.index') },
                        { label: 'Buscar disponibilidad' },
                    ]}
                    title="Buscar disponibilidad"
                    description={`Encuentra día, hora, docente y aula libres para asignar clase a un grupo · ${periodo.nombre}`}
                />

                <Card>
                    <div className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Grupo(s)</label>
                            <p className="mt-0.5 text-xs text-slate-400 dark:text-slate-500">
                                Normalmente uno solo; marca varios si la clase se compartirá entre grupos.
                            </p>
                            <div className="mt-1 max-h-40 space-y-1 overflow-y-auto rounded-md border border-slate-300 p-2 dark:border-slate-700">
                                {grupos.map((g) => (
                                    <label
                                        key={g.id}
                                        className="flex cursor-pointer items-center gap-2 rounded px-1.5 py-1 text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800"
                                    >
                                        <input
                                            type="checkbox"
                                            className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600"
                                            checked={grupoIds.includes(g.id)}
                                            onChange={(e) => {
                                                setGrupoIds((prev) => (e.target.checked ? [...prev, g.id] : prev.filter((id) => id !== g.id)));
                                                setPropuestas(null);
                                                setDias(null);
                                                setMensaje(null);
                                            }}
                                        />
                                        {g.nombre} · {g.carrera_nombre} ({g.matricula} alumnos)
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Asignatura</label>
                                <SelectInput className="mt-1 block w-full" value={asignaturaId} onChange={(e) => setAsignaturaId(e.target.value)}>
                                    <option value="">Selecciona una asignatura</option>
                                    {asignaturas.map((a) => (
                                        <option key={a.id} value={a.id}>
                                            {a.nombre}
                                            {a.semestre ? ` · sem. ${a.semestre}` : ''}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Docente (opcional)</label>
                                <SelectInput
                                    className="mt-1 block w-full"
                                    value={docenteFiltroId}
                                    onChange={(e) => setDocenteFiltroId(e.target.value)}
                                    disabled={docentesFiltro.length === 0}
                                >
                                    <option value="">Cualquier docente de la carrera</option>
                                    {docentesFiltro.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nombre}
                                        </option>
                                    ))}
                                </SelectInput>
                            </div>
                        </div>

                        <div>
                            <PrimaryButton disabled={grupoIds.length === 0 || buscando} onClick={buscar}>
                                {buscando ? 'Buscando…' : 'Buscar disponibilidad'}
                            </PrimaryButton>
                        </div>
                    </div>
                </Card>

                {mensaje && (
                    <p
                        className={`rounded-lg p-4 text-sm ring-1 ring-inset ${
                            mensaje.tipo === 'ok'
                                ? 'bg-emerald-50 text-emerald-800 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20'
                                : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20'
                        }`}
                    >
                        {mensaje.texto}
                    </p>
                )}

                {propuestas && propuestas.length === 0 && (
                    <p className="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                        No se encontró ningún hueco libre para el/los grupo(s), docente y filtros elegidos.
                    </p>
                )}

                {docentesConPropuestas.length > 0 && (
                    <Card>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">
                                Huecos libres <span className="font-normal text-slate-400">({propuestas.length})</span>
                            </h3>
                            <div className="flex flex-wrap gap-2">
                                {docentesConPropuestas.map((d) => (
                                    <button
                                        key={d.id}
                                        type="button"
                                        onClick={() => setDocenteActivoId(d.id)}
                                        className={`rounded-full px-3 py-1 text-xs font-medium transition ${
                                            docenteActivoId === d.id
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
                                        }`}
                                    >
                                        {d.nombre} ({d.total})
                                    </button>
                                ))}
                            </div>
                        </div>

                        <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Haz clic en una celda verde para asignar la clase ahí mismo. El resto del grid muestra la disponibilidad y ocupación
                            real del docente, igual que en el constructor de cargas.
                        </p>

                        <div className="mt-3 flex flex-wrap gap-4 text-xs text-slate-600 dark:text-slate-400">
                            <Leyenda clase="bg-emerald-100 ring-1 ring-inset ring-emerald-400 dark:bg-emerald-500/20" texto="Propuesta libre" />
                            <Leyenda clase="bg-white border border-slate-300 dark:bg-slate-900 dark:border-slate-600" texto="Disponible" />
                            <Leyenda clase="bg-indigo-100 dark:bg-indigo-500/20" texto="Esta carrera" />
                            <Leyenda clase="bg-amber-100 dark:bg-amber-500/20" texto="Ocupado (otra carrera)" />
                            <Leyenda clase="bg-slate-100 dark:bg-slate-800" texto="Fuera de disponibilidad" />
                        </div>

                        <div className="mt-4">
                            {cargandoGrid || !diasConPropuestas ? (
                                <p className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">Cargando horario…</p>
                            ) : (
                                <fieldset disabled={asignando} className="disabled:opacity-60">
                                    <WeekGrid dias={diasConPropuestas} slots={slots} onSeleccion={() => {}} onClickPropuesta={(c) => asignar(c.propuesta)} />
                                </fieldset>
                            )}
                        </div>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function Leyenda({ clase, texto }) {
    return (
        <span className="flex items-center gap-1">
            <span className={`inline-block h-3 w-3 rounded-sm ${clase}`} />
            {texto}
        </span>
    );
}
