import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

const DIAS = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

export default function Disponibilidad({ periodo, grupoPreseleccionadoId, grupos, asignaturas }) {
    const [grupoIds, setGrupoIds] = useState(grupoPreseleccionadoId ? [grupoPreseleccionadoId] : []);
    const [asignaturaId, setAsignaturaId] = useState('');
    const [docenteId, setDocenteId] = useState('');
    const [docentes, setDocentes] = useState([]);
    const [propuestas, setPropuestas] = useState(null);
    const [buscando, setBuscando] = useState(false);
    const [asignando, setAsignando] = useState(null);
    const [mensaje, setMensaje] = useState(null);

    const gruposSeleccionados = useMemo(() => grupos.filter((g) => grupoIds.includes(g.id)), [grupos, grupoIds]);

    useEffect(() => {
        if (grupoIds.length === 0) {
            setDocentes([]);
            setDocenteId('');
            return;
        }

        window.axios
            .post(route('admin.cargas.disponibilidad.docentes'), {
                periodo_escolar_id: periodo.id,
                grupo_ids: grupoIds,
            })
            .then((res) => setDocentes(res.data.docentes));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [grupoIds.join(',')]);

    const alternarGrupo = (grupoId, marcado) => {
        setGrupoIds((prev) => (marcado ? [...prev, grupoId] : prev.filter((id) => id !== grupoId)));
        setPropuestas(null);
        setMensaje(null);
    };

    const buscar = () => {
        if (grupoIds.length === 0) {
            return;
        }

        setBuscando(true);
        setMensaje(null);
        window.axios
            .post(route('admin.cargas.disponibilidad.buscar'), {
                periodo_escolar_id: periodo.id,
                grupo_ids: grupoIds,
                asignatura_id: asignaturaId || null,
                docente_ids: docenteId ? [docenteId] : null,
            })
            .then((res) => setPropuestas(res.data.propuestas))
            .finally(() => setBuscando(false));
    };

    const asignar = (propuesta) => {
        if (!asignaturaId) {
            setMensaje({ tipo: 'error', texto: 'Selecciona una asignatura antes de asignar.' });
            return;
        }

        const clave = `${propuesta.dia_semana}-${propuesta.hora_inicio}-${propuesta.docente_id}-${propuesta.aula_id}`;
        setAsignando(clave);
        setMensaje(null);

        window.axios
            .post(route('admin.cargas.store'), {
                periodo_escolar_id: periodo.id,
                carrera_id: gruposSeleccionados[0]?.carrera_id,
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
                setMensaje({ tipo: 'ok', texto: `Clase asignada: ${DIAS[propuesta.dia_semana]} ${propuesta.hora_inicio}-${propuesta.hora_fin}.` });
                setPropuestas((prev) => prev?.filter((p) => p !== propuesta) ?? null);
            })
            .catch((err) => {
                const primerError = Object.values(err.response?.data?.errors ?? {})[0]?.[0];
                setMensaje({ tipo: 'error', texto: primerError ?? 'No se pudo guardar la asignación.' });
            })
            .finally(() => setAsignando(null));
    };

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
                                            onChange={(e) => alternarGrupo(g.id, e.target.checked)}
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
                                    value={docenteId}
                                    onChange={(e) => setDocenteId(e.target.value)}
                                    disabled={docentes.length === 0}
                                >
                                    <option value="">Cualquier docente de la carrera</option>
                                    {docentes.map((d) => (
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

                {propuestas && (
                    <Card>
                        <h3 className="text-sm font-semibold text-slate-900 dark:text-white">
                            Propuestas <span className="font-normal text-slate-400">({propuestas.length})</span>
                        </h3>

                        {propuestas.length === 0 ? (
                            <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                No se encontró ningún hueco libre para el/los grupo(s), docente y filtros elegidos.
                            </p>
                        ) : (
                            <ul className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                                {propuestas.map((p) => {
                                    const clave = `${p.dia_semana}-${p.hora_inicio}-${p.docente_id}-${p.aula_id}`;
                                    return (
                                        <li key={clave} className="flex items-center justify-between gap-4 py-3">
                                            <div className="text-sm">
                                                <span className="font-medium text-slate-900 dark:text-white">
                                                    {DIAS[p.dia_semana]} {p.hora_inicio} - {p.hora_fin}
                                                    {p.modulo_sabatino ? ` · Módulo ${p.modulo_sabatino}` : ''}
                                                </span>
                                                <span className="ml-2 text-slate-500 dark:text-slate-400">
                                                    {p.docente_nombre} · Aula {p.aula_nombre}
                                                </span>
                                            </div>
                                            <PrimaryButton
                                                className="shrink-0"
                                                disabled={asignando === clave || !asignaturaId}
                                                onClick={() => asignar(p)}
                                            >
                                                {asignando === clave ? 'Asignando…' : 'Asignar'}
                                            </PrimaryButton>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
