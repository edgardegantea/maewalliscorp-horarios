import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import { Link, router, useForm } from '@inertiajs/react';

export default function Coordinadores({ carrera, coordinadoresDisponibles }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        user_id: '',
    });

    const agregar = (e) => {
        e.preventDefault();
        post(route('admin.carreras.coordinadores.store', carrera.id), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    const quitar = (coordinador) => {
        if (confirm(`¿Quitar a ${coordinador.name} como coordinador de esta carrera?`)) {
            router.delete(
                route('admin.carreras.coordinadores.destroy', [carrera.id, coordinador.id]),
                { preserveScroll: true },
            );
        }
    };

    const asignadosIds = carrera.coordinadores.map((c) => c.id);
    const disponibles = coordinadoresDisponibles.filter((c) => !asignadosIds.includes(c.id));

    return (
        <div className="mt-8 border-t border-slate-200 pt-6 dark:border-slate-700">
            <h3 className="text-lg font-medium text-slate-900 dark:text-white">Coordinadores</h3>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Los coordinadores solo pueden gestionar asignaturas, grupos y cargas académicas de las carreras
                que tengan asignadas aquí.
            </p>

            <ul className="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
                {carrera.coordinadores.map((coordinador) => (
                    <li key={coordinador.id} className="flex items-center justify-between py-2 text-sm text-slate-700 dark:text-slate-300">
                        <span>{coordinador.name}</span>
                        <button
                            onClick={() => quitar(coordinador)}
                            className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        >
                            Quitar
                        </button>
                    </li>
                ))}
                {carrera.coordinadores.length === 0 && (
                    <li className="py-2 text-sm text-slate-500 dark:text-slate-400">Sin coordinadores asignados.</li>
                )}
            </ul>

            <form onSubmit={agregar} className="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <SelectInput value={data.user_id} onChange={(e) => setData('user_id', e.target.value)}>
                        <option value="">Selecciona un coordinador</option>
                        {disponibles.map((coordinador) => (
                            <option key={coordinador.id} value={coordinador.id}>
                                {coordinador.name}
                            </option>
                        ))}
                    </SelectInput>
                    <InputError message={errors.user_id} className="mt-1" />
                </div>

                <SecondaryButton type="submit" disabled={processing || !data.user_id}>
                    Agregar coordinador
                </SecondaryButton>
            </form>

            <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">
                ¿No aparece el coordinador que buscas?{' '}
                <Link href={route('admin.coordinadores.create')} className="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                    Crea uno nuevo
                </Link>
                .
            </p>
        </div>
    );
}
