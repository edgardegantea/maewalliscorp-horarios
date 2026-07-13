import SelectInput from '@/Components/SelectInput';
import SlotModal from '@/Components/CargaGrid/SlotModal';
import WeekGrid from '@/Components/CargaGrid/WeekGrid';
import Card from '@/Components/ui/Card';
import PageHeader from '@/Components/ui/PageHeader';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';

export default function Builder({ periodo, carrera, docentes, asignaturas, grupos, aulas, slots }) {
    const [docenteId, setDocenteId] = useState(docentes[0]?.id ?? '');
    const [dias, setDias] = useState(null);
    const [cargando, setCargando] = useState(false);
    const [seleccion, setSeleccion] = useState(null);
    const [modalAbierto, setModalAbierto] = useState(false);

    const cargarGrid = useCallback(() => {
        if (!docenteId) {
            setDias(null);
            return;
        }
        setCargando(true);
        window.axios
            .get(route('admin.cargas.grid-data'), {
                params: { periodo: periodo.id, carrera: carrera.id, docente: docenteId },
            })
            .then((res) => setDias(res.data.dias))
            .finally(() => setCargando(false));
    }, [docenteId, periodo.id, carrera.id]);

    useEffect(() => {
        cargarGrid();
    }, [cargarGrid]);

    const alSeleccionar = useCallback((sel) => {
        setSeleccion(sel);
        setModalAbierto(true);
    }, []);

    const cerrarModal = (guardado) => {
        setModalAbierto(false);
        setSeleccion(null);
        if (guardado) {
            cargarGrid();
        }
    };

    const eliminarReservado = (celda) => {
        if (confirm('¿Eliminar esta carga académica?')) {
            router.delete(route('admin.cargas.destroy', celda.carga_id), {
                preserveScroll: true,
                onSuccess: () => cargarGrid(),
            });
        }
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-base font-semibold text-slate-900 dark:text-white">Nueva carga académica</h2>}>
            <Head title="Constructor de cargas académicas" />

            <div className="space-y-6">
                <PageHeader
                    breadcrumbs={[
                        { label: 'Dashboard', href: route('dashboard') },
                        { label: 'Cargas académicas', href: route('admin.cargas.index') },
                        { label: periodo.nombre, href: route('admin.cargas.index', { periodo: periodo.id }) },
                        { label: carrera.nombre, href: route('admin.cargas.index', { periodo: periodo.id, carrera: carrera.id }) },
                        { label: 'Nueva carga académica' },
                    ]}
                    title="Nueva carga académica"
                    description={`${carrera.nombre} · ${periodo.nombre}`}
                    actions={
                        <Link
                            href={route('admin.cargas.index', { periodo: periodo.id, carrera: carrera.id })}
                            className="text-sm font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
                        >
                            ← Volver al listado
                        </Link>
                    }
                />

                <Card>
                    <div className="flex flex-wrap items-center gap-4">
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300">Docente</label>
                            <SelectInput
                                className="mt-1 block w-72"
                                value={docenteId}
                                onChange={(e) => setDocenteId(e.target.value)}
                            >
                                {docentes.length === 0 && <option value="">Sin docentes asignados</option>}
                                {docentes.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.nombre}
                                    </option>
                                ))}
                            </SelectInput>
                        </div>

                        <div className="ml-auto flex flex-wrap gap-4 text-xs text-slate-600 dark:text-slate-400">
                            <Leyenda clase="bg-white border border-slate-300 dark:bg-slate-900 dark:border-slate-600" texto="Disponible" />
                            <Leyenda clase="bg-indigo-100 dark:bg-indigo-500/20" texto="Esta carrera" />
                            <Leyenda clase="bg-amber-100 dark:bg-amber-500/20" texto="Ocupado (otra carrera)" />
                            <Leyenda clase="bg-slate-100 dark:bg-slate-800" texto="Fuera de disponibilidad" />
                        </div>
                    </div>
                </Card>

                {docentes.length === 0 ? (
                    <p className="rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20">
                        No hay docentes asignados a esta carrera en este periodo. Asigna docentes desde su
                        ficha antes de crear cargas académicas.
                    </p>
                ) : (
                    <Card>
                        <p className="mb-4 text-sm text-slate-500 dark:text-slate-400">
                            Haz clic en una hora disponible o arrastra para seleccionar un rango contiguo. Se
                            abrirá una ventana para elegir asignatura, grupo y aula.
                        </p>
                        {cargando || !dias ? (
                            <p className="py-8 text-center text-sm text-slate-400 dark:text-slate-500">Cargando horario…</p>
                        ) : (
                            <WeekGrid
                                dias={dias}
                                slots={slots}
                                onSeleccion={alSeleccionar}
                                onClickReservado={eliminarReservado}
                            />
                        )}
                    </Card>
                )}
            </div>

            {docenteId && (
                <SlotModal
                    show={modalAbierto}
                    onClose={cerrarModal}
                    seleccion={seleccion}
                    contexto={{ periodo, carrera, docenteId }}
                    asignaturas={asignaturas}
                    grupos={grupos}
                    aulas={aulas}
                />
            )}
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
