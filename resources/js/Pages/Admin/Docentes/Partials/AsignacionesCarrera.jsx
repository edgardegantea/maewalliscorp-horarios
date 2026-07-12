import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import { router, useForm } from '@inertiajs/react';

export default function AsignacionesCarrera({ docente, carreras, periodos }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        carrera_id: '',
        periodo_escolar_id: '',
    });

    const agregar = (e) => {
        e.preventDefault();
        post(route('admin.docentes.carreras.store', docente.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const quitar = (docenteCarrera) => {
        if (confirm('¿Quitar esta asignación de carrera?')) {
            router.delete(
                route('admin.docentes.carreras.destroy', [docente.id, docenteCarrera.id]),
                { preserveScroll: true },
            );
        }
    };

    return (
        <div className="mt-8 border-t border-slate-200 pt-6 dark:border-slate-700">
            <h3 className="text-lg font-medium text-slate-900 dark:text-white">Asignación a carreras</h3>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Solo los docentes asignados a una carrera en un periodo aparecen como seleccionables al crear cargas académicas.
            </p>

            <ul className="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
                {docente.docente_carreras.map((dc) => (
                    <li key={dc.id} className="flex items-center justify-between py-2 text-sm text-slate-700 dark:text-slate-300">
                        <span>
                            {dc.carrera.nombre} — {dc.periodo_escolar.nombre}
                        </span>
                        <button
                            onClick={() => quitar(dc)}
                            className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Quitar
                        </button>
                    </li>
                ))}
                {docente.docente_carreras.length === 0 && (
                    <li className="py-2 text-sm text-slate-500 dark:text-slate-400">Sin asignaciones todavía.</li>
                )}
            </ul>

            <form onSubmit={agregar} className="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <SelectInput
                        value={data.carrera_id}
                        onChange={(e) => setData('carrera_id', e.target.value)}
                    >
                        <option value="">Carrera</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.nombre}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.carrera_id} className="mt-1" />
                </div>

                <div>
                    <SelectInput
                        value={data.periodo_escolar_id}
                        onChange={(e) => setData('periodo_escolar_id', e.target.value)}
                    >
                        <option value="">Periodo</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.periodo_escolar_id} className="mt-1" />
                </div>

                <SecondaryButton
                    type="submit"
                    disabled={processing || !data.carrera_id || !data.periodo_escolar_id}
                >
                    Agregar asignación
                </SecondaryButton>
            </form>
        </div>
    );
}
