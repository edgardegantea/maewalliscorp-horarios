import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import { Link } from '@inertiajs/react';

export default function Form({ data, setData, errors, processing, onSubmit, cancelHref, carreras }) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <div>
                <InputLabel htmlFor="carrera_id" value="Carrera" />
                <SelectInput
                    id="carrera_id"
                    className="mt-1 block w-full"
                    value={data.carrera_id}
                    onChange={(e) => setData('carrera_id', e.target.value)}
                >
                    <option value="">Selecciona una carrera</option>
                    {carreras.map((carrera) => (
                        <option key={carrera.id} value={carrera.id}>
                            {carrera.nombre}
                        </option>
                    ))}
                </SelectInput>
                <InputError message={errors.carrera_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="nombre" value="Nombre" />
                <TextInput
                    id="nombre"
                    className="mt-1 block w-full"
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                />
                <InputError message={errors.nombre} className="mt-2" />
            </div>

            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <InputLabel htmlFor="clave" value="Clave" />
                    <TextInput
                        id="clave"
                        className="mt-1 block w-full"
                        placeholder="PROG1"
                        value={data.clave ?? ''}
                        onChange={(e) => setData('clave', e.target.value.toUpperCase())}
                    />
                    <InputError message={errors.clave} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="horas_semana" value="Horas por semana (opcional)" />
                    <TextInput
                        id="horas_semana"
                        type="number"
                        min="1"
                        className="mt-1 block w-full"
                        value={data.horas_semana ?? ''}
                        onChange={(e) => setData('horas_semana', e.target.value)}
                    />
                    <InputError message={errors.horas_semana} className="mt-2" />
                </div>
            </div>

            <div>
                <InputLabel htmlFor="semestre" value="Semestre (opcional)" />
                <TextInput
                    id="semestre"
                    type="number"
                    min="1"
                    className="mt-1 block w-full sm:w-1/2"
                    value={data.semestre ?? ''}
                    onChange={(e) => setData('semestre', e.target.value)}
                />
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Si lo defines, se mostrará una advertencia al asignar esta materia a un grupo de otro semestre.
                </p>
                <InputError message={errors.semestre} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="modulo_sabatino" value="Módulo sabatino (opcional)" />
                <SelectInput
                    id="modulo_sabatino"
                    className="mt-1 block w-full sm:w-1/2"
                    value={data.modulo_sabatino ?? ''}
                    onChange={(e) => setData('modulo_sabatino', e.target.value)}
                >
                    <option value="">No aplica</option>
                    <option value="1">Módulo 1</option>
                    <option value="2">Módulo 2</option>
                </SelectInput>
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Para grupos que tienen clase el sábado (terminados en "F" o "B"), cuyo semestre se divide en dos
                    módulos de hasta 3 materias cada uno. Indica en cuál se imparte esta asignatura.
                </p>
                <InputError message={errors.modulo_sabatino} className="mt-2" />
            </div>

            <div className="flex items-center gap-4">
                <PrimaryButton disabled={processing}>Guardar</PrimaryButton>
                <Link href={cancelHref} className="text-sm text-slate-600 underline dark:text-slate-400 dark:hover:text-slate-200">
                    Cancelar
                </Link>
            </div>
        </form>
    );
}
